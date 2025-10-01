<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\AnnouncementRequest;
use App\Models\Announcement;
use App\Services\AnnouncementService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    protected $announcementService;

    public function __construct(AnnouncementService $announcementService)
    {
        $this->announcementService = $announcementService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['type', 'priority', 'published', 'featured']);
            $announcements = $this->announcementService->getAll($filters);
            $statistics = $this->announcementService->getStatistics();

            return Inertia::render('Staff/Announcements/Index', [
                'announcements' => $announcements,
                'statistics' => $statistics,
                'filters' => $filters,
            ]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error('AnnouncementController index error: ' . $e->getMessage());

            // Return with empty data for now
            return Inertia::render('Staff/Announcements/Index', [
                'announcements' => collect([]),
                'statistics' => [
                    'total' => 0,
                    'published' => 0,
                    'draft' => 0,
                    'featured' => 0,
                    'expired' => 0,
                    'by_type' => [],
                    'by_priority' => [],
                ],
                'filters' => [],
            ]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Staff/Announcements/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AnnouncementRequest $request)
    {
        try {
            $data = $request->validated();
            $image = $request->file('image');
            $attachments = $request->file('attachments', []);

            $announcement = $this->announcementService->create($data, $image, $attachments);

            return redirect()->route('staff.announcements.index')
                ->with('success', 'Announcement created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create announcement: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Announcement $announcement)
    {
        return Inertia::render('Staff/Announcements/Show', [
            'announcement' => $announcement,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Announcement $announcement)
    {
        return Inertia::render('Staff/Announcements/Edit', [
            'announcement' => $announcement,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AnnouncementRequest $request, Announcement $announcement)
    {
        try {
            $data = $request->validated();
            $image = $request->file('image');
            $attachments = $request->file('attachments', []);

            $this->announcementService->update($announcement, $data, $image, $attachments);

            return redirect()->route('staff.announcements.index')
                ->with('success', 'Announcement updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update announcement: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Announcement $announcement)
    {
        try {
            $this->announcementService->delete($announcement);

            return redirect()->route('staff.announcements.index')
                ->with('success', 'Announcement deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete announcement: ' . $e->getMessage());
        }
    }

    /**
     * Toggle publication status
     */
    public function togglePublication(Announcement $announcement)
    {
        try {
            $this->announcementService->togglePublication($announcement);
            $status = $announcement->fresh()->is_published ? 'published' : 'unpublished';

            return response()->json([
                'success' => true,
                'message' => "Announcement {$status} successfully.",
                'announcement' => $announcement->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle publication status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(Announcement $announcement)
    {
        try {
            $this->announcementService->toggleFeatured($announcement);
            $status = $announcement->fresh()->is_featured ? 'featured' : 'unfeatured';

            return response()->json([
                'success' => true,
                'message' => "Announcement {$status} successfully.",
                'announcement' => $announcement->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle featured status: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(Announcement $announcement, Request $request)
    {
        try {
            $attachmentPath = $request->input('attachment_path');
            $this->announcementService->deleteAttachment($announcement, $attachmentPath);

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully.',
                'announcement' => $announcement->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reorder announcements
     */
    public function reorder(Request $request)
    {
        try {
            $announcementIds = $request->input('announcement_ids', []);
            $this->announcementService->reorder($announcementIds);

            return response()->json([
                'success' => true,
                'message' => 'Announcements reordered successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder announcements: ' . $e->getMessage(),
            ], 500);
        }
    }
}
