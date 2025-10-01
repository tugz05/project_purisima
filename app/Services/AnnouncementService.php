<?php

namespace App\Services;

use App\Models\Announcement;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AnnouncementService
{
    /**
     * Get all announcements with optional filters
     */
    public function getAll(array $filters = [])
    {
        $query = Announcement::query();

        // Apply filters
        if (isset($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (isset($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }

        if (isset($filters['published'])) {
            if ($filters['published']) {
                $query->published();
            } else {
                $query->where('is_published', false);
            }
        }

        if (isset($filters['featured'])) {
            $query->featured();
        }

        return $query->ordered()->get();
    }

    /**
     * Get published announcements for public display
     */
    public function getPublished(int $limit = null)
    {
        $query = Announcement::published()->ordered();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Get featured announcements
     */
    public function getFeatured(int $limit = 3)
    {
        return Announcement::published()
            ->featured()
            ->ordered()
            ->limit($limit)
            ->get();
    }

    /**
     * Create a new announcement
     */
    public function create(array $data, ?UploadedFile $image = null, array $attachments = [])
    {
        // Handle image upload
        if ($image) {
            $data['image_path'] = $this->storeImage($image);
        }

        // Handle attachments
        if (!empty($attachments)) {
            $data['attachments'] = $this->storeAttachments($attachments);
        }

        // Set published_at if is_published is true and published_at is not set
        if ($data['is_published'] && !isset($data['published_at'])) {
            $data['published_at'] = now();
        }

        // Set sort_order if not provided
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = $this->getNextSortOrder();
        }

        return Announcement::create($data);
    }

    /**
     * Update an announcement
     */
    public function update(Announcement $announcement, array $data, ?UploadedFile $image = null, array $attachments = [])
    {
        // Handle image upload
        if ($image) {
            // Delete old image if exists
            if ($announcement->image_path) {
                $this->deleteImage($announcement->image_path);
            }
            $data['image_path'] = $this->storeImage($image);
        }

        // Handle new attachments
        if (!empty($attachments)) {
            $existingAttachments = $announcement->attachments ?? [];
            $newAttachments = $this->storeAttachments($attachments);
            $data['attachments'] = array_merge($existingAttachments, $newAttachments);
        }

        // Set published_at if is_published is true and published_at is not set
        if ($data['is_published'] && !$announcement->published_at) {
            $data['published_at'] = now();
        }

        $announcement->update($data);

        return $announcement;
    }

    /**
     * Delete an announcement
     */
    public function delete(Announcement $announcement)
    {
        // Delete associated files
        if ($announcement->image_path) {
            $this->deleteImage($announcement->image_path);
        }

        if ($announcement->attachments) {
            $this->deleteAttachments($announcement->attachments);
        }

        return $announcement->delete();
    }

    /**
     * Toggle publication status
     */
    public function togglePublication(Announcement $announcement)
    {
        $announcement->update([
            'is_published' => !$announcement->is_published,
            'published_at' => !$announcement->is_published ? now() : $announcement->published_at,
        ]);

        return $announcement;
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Announcement $announcement)
    {
        $announcement->update([
            'is_featured' => !$announcement->is_featured,
        ]);

        return $announcement;
    }

    /**
     * Delete attachment from announcement
     */
    public function deleteAttachment(Announcement $announcement, string $attachmentPath)
    {
        $attachments = $announcement->attachments ?? [];
        $updatedAttachments = array_filter($attachments, function ($attachment) use ($attachmentPath) {
            return $attachment['path'] !== $attachmentPath;
        });

        $announcement->update(['attachments' => array_values($updatedAttachments)]);

        // Delete file from storage
        Storage::disk('public')->delete($attachmentPath);

        return $announcement;
    }

    /**
     * Store image file
     */
    private function storeImage(UploadedFile $image): string
    {
        $filename = Str::uuid() . '.' . $image->getClientOriginalExtension();
        $path = $image->storeAs('announcements/images', $filename, 'public');

        return $path;
    }

    /**
     * Store attachment files
     */
    private function storeAttachments(array $attachments): array
    {
        $storedAttachments = [];

        foreach ($attachments as $attachment) {
            $filename = Str::uuid() . '.' . $attachment->getClientOriginalExtension();
            $path = $attachment->storeAs('announcements/attachments', $filename, 'public');

            $storedAttachments[] = [
                'name' => $attachment->getClientOriginalName(),
                'path' => $path,
                'size' => $attachment->getSize(),
                'mime_type' => $attachment->getMimeType(),
                'uploaded_at' => now()->toISOString(),
            ];
        }

        return $storedAttachments;
    }

    /**
     * Delete image file
     */
    private function deleteImage(string $imagePath): void
    {
        Storage::disk('public')->delete($imagePath);
    }

    /**
     * Delete attachment files
     */
    private function deleteAttachments(array $attachments): void
    {
        foreach ($attachments as $attachment) {
            Storage::disk('public')->delete($attachment['path']);
        }
    }

    /**
     * Get next sort order
     */
    private function getNextSortOrder(): int
    {
        $maxOrder = Announcement::max('sort_order') ?? 0;
        return $maxOrder + 1;
    }

    /**
     * Reorder announcements
     */
    public function reorder(array $announcementIds): void
    {
        foreach ($announcementIds as $index => $id) {
            Announcement::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }

    /**
     * Get announcement statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => Announcement::count(),
            'published' => Announcement::where('is_published', true)->count(),
            'draft' => Announcement::where('is_published', false)->count(),
            'featured' => Announcement::where('is_featured', true)->count(),
            'expired' => Announcement::where('expires_at', '<', now())->count(),
            'by_type' => Announcement::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_priority' => Announcement::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
        ];
    }
}
