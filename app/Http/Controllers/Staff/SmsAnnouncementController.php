<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\SmsBroadcast;
use App\Models\SmsOutboundMessage;
use App\Services\SmsBroadcastService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SmsAnnouncementController extends Controller
{
    public function __construct(private SmsBroadcastService $broadcastService) {}

    public function index(): Response
    {
        $broadcasts = SmsBroadcast::with('creator')
            ->latest()
            ->paginate(15);

        return Inertia::render('Staff/Sms/Index', [
            'broadcasts' => $broadcasts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:1600'],
        ]);

        $this->broadcastService->broadcastToResidents(
            $validated['title'],
            $validated['message'],
            $request->user()
        );

        return redirect()->route('staff.sms.index')
            ->with('success', 'SMS broadcast queued and will be sent to all residents with phone numbers.');
    }

    public function recipients(SmsBroadcast $broadcast): JsonResponse
    {
        $messages = SmsOutboundMessage::query()
            ->where('context_type', 'broadcast')
            ->where('context_id', $broadcast->id)
            ->orderByDesc('created_at')
            ->get(['to', 'status', 'error_message', 'sent_at', 'created_at']);

        return response()->json([
            'broadcast' => [
                'id'               => $broadcast->id,
                'title'            => $broadcast->title,
                'recipients_count' => $broadcast->recipients_count,
                'sent_count'       => $broadcast->sent_count,
                'failed_count'     => $broadcast->failed_count,
                'status'           => $broadcast->status,
            ],
            'messages' => $messages,
        ]);
    }
}
