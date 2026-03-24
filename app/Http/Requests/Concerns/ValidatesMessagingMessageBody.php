<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Http\UploadedFile;

trait ValidatesMessagingMessageBody
{
    public function messagingContent(): string
    {
        return trim((string) $this->input('content', ''));
    }

    /**
     * @return list<UploadedFile>
     */
    public function messagingFiles(): array
    {
        $files = $this->file('attachments');

        if ($files instanceof UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter(
            $files,
            static fn ($f) => $f instanceof UploadedFile
        ));
    }

    public function hasMessagingMessageBody(): bool
    {
        if ($this->messagingContent() !== '') {
            return true;
        }

        return count($this->messagingFiles()) > 0;
    }
}
