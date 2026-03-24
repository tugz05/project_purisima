<?php

namespace App\Services;

use App\Models\PendingFileUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PendingFileUploadService
{
    private const TTL_HOURS = 24;

    public const PURPOSE_TRANSACTION_SUBMISSION = 'transaction_submission';

    public const PURPOSE_PAYMENT_PROOF = 'payment_proof';

    public function store(UploadedFile $file, User $user, string $purpose): PendingFileUpload
    {
        $id = (string) Str::ulid();
        $extension = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $path = $file->storeAs(
            'pending-uploads/'.$user->id,
            $id.'.'.$extension,
            'public'
        );

        return PendingFileUpload::create([
            'id' => $id,
            'user_id' => $user->id,
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) ($file->getMimeType() ?? 'application/octet-stream'),
            'size' => $file->getSize(),
            'purpose' => $purpose,
            'expires_at' => now()->addHours(self::TTL_HOURS),
        ]);
    }

    /**
     * Move a pending upload into a final directory and delete the pending row.
     *
     * @return array{name: string, path: string, size: int, mime_type: string}
     */
    public function consume(PendingFileUpload $pending, User $user, string $expectedPurpose, string $finalDirectoryPrefix): array
    {
        if ((int) $pending->user_id !== (int) $user->id) {
            throw new \InvalidArgumentException('Unauthorized file reference.');
        }

        if ($pending->purpose !== $expectedPurpose) {
            throw new \InvalidArgumentException('Invalid upload purpose.');
        }

        if ($pending->isExpired()) {
            Storage::disk($pending->disk)->delete($pending->path);
            $pending->delete();

            throw new \InvalidArgumentException('Upload expired. Please upload the file again.');
        }

        $disk = Storage::disk($pending->disk);
        if (! $disk->exists($pending->path)) {
            $pending->delete();

            throw new \InvalidArgumentException('Upload not found. Please upload the file again.');
        }

        $finalName = $finalDirectoryPrefix.'/'.basename($pending->path);
        $disk->move($pending->path, $finalName);

        $meta = [
            'name' => $pending->original_name,
            'path' => $finalName,
            'size' => $pending->size,
            'mime_type' => $pending->mime_type,
        ];

        $pending->delete();

        return $meta;
    }

    /**
     * @param  array<int, string>  $ids
     * @return list<array{name: string, path: string, size: int, mime_type: string}>
     */
    public function consumeIds(User $user, array $ids, string $expectedPurpose, string $finalDirectoryPrefix): array
    {
        $out = [];
        foreach ($ids as $id) {
            $pending = PendingFileUpload::query()
                ->whereKey($id)
                ->where('user_id', $user->id)
                ->first();

            if (! $pending) {
                throw new \InvalidArgumentException('One or more uploads are invalid or already used.');
            }

            $out[] = $this->consume($pending, $user, $expectedPurpose, $finalDirectoryPrefix);
        }

        return $out;
    }

    public function deleteIfOwned(?PendingFileUpload $pending, User $user): void
    {
        if (! $pending || (int) $pending->user_id !== (int) $user->id) {
            return;
        }

        Storage::disk($pending->disk)->delete($pending->path);
        $pending->delete();
    }
}
