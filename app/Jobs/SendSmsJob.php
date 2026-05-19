<?php

namespace App\Jobs;

use App\Models\SmsBroadcast;
use App\Models\SmsOutboundMessage;
use App\Services\Sms\SmsGatewayInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly string $to,
        public readonly string $message,
        public readonly ?string $contextType = null,
        public readonly ?int $contextId = null,
    ) {
        $this->onQueue('sms');
    }

    public function handle(SmsGatewayInterface $gateway): void
    {
        $record = SmsOutboundMessage::create([
            'to' => $this->to,
            'message' => $this->message,
            'context_type' => $this->contextType,
            'context_id' => $this->contextId,
            'status' => 'pending',
            'attempt_number' => $this->attempts(),
        ]);

        try {
            $sid = $gateway->send($this->to, $this->message);

            $record->update([
                'status' => 'sent',
                'provider_message_id' => $sid,
                'sent_at' => now(),
            ]);

            if ($this->contextType === 'broadcast' && $this->contextId) {
                SmsBroadcast::where('id', $this->contextId)->increment('sent_count');
                $this->checkBroadcastCompletion();
            }

            Log::info('[SMS] Sent successfully', [
                'to' => $this->to,
                'sid' => $sid,
                'context' => $this->contextType.'#'.$this->contextId,
            ]);
        } catch (\Throwable $e) {
            $record->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            if ($this->contextType === 'broadcast' && $this->contextId) {
                SmsBroadcast::where('id', $this->contextId)->increment('failed_count');
                $this->checkBroadcastCompletion();
            }

            Log::error('[SMS] Failed to send', [
                'to' => $this->to,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    /** Exponential backoff: 10 s → 60 s → 300 s between retries. */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[SMS] Job permanently failed after all retries', [
            'to' => $this->to,
            'message' => $this->message,
            'error' => $exception->getMessage(),
        ]);

        if ($this->contextType === 'broadcast' && $this->contextId) {
            SmsBroadcast::where('id', $this->contextId)->increment('failed_count');
            $this->checkBroadcastCompletion();
        }
    }

    private function checkBroadcastCompletion(): void
    {
        $broadcast = SmsBroadcast::find($this->contextId);
        if (! $broadcast) {
            return;
        }

        $done = $broadcast->sent_count + $broadcast->failed_count;
        if ($done >= $broadcast->recipients_count) {
            $broadcast->update([
                'status' => $broadcast->failed_count === $broadcast->recipients_count ? 'failed' : 'completed',
                'completed_at' => now(),
            ]);
        }
    }
}
