<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePendingFileUploadRequest;
use App\Models\PendingFileUpload;
use App\Services\PendingFileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PendingFileUploadController extends Controller
{
    public function __construct(
        private PendingFileUploadService $pendingFileUploadService
    ) {}

    public function store(StorePendingFileUploadRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $file = $request->file('file');
        $purpose = (string) $request->validated('purpose');

        $pending = $this->pendingFileUploadService->store($file, $user, $purpose);

        return response()->json([
            'id' => $pending->id,
            'original_name' => $pending->original_name,
            'size' => $pending->size,
            'mime_type' => $pending->mime_type,
        ]);
    }

    public function destroy(Request $request, PendingFileUpload $pendingFileUpload): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->pendingFileUploadService->deleteIfOwned($pendingFileUpload, $user);

        return response()->json(['success' => true]);
    }
}
