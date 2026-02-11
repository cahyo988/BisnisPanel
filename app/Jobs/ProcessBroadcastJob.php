<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Services\MessageDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBroadcastJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @param  list<int>  $logIds
     */
    public function __construct(
        private readonly array $logIds,
        private readonly int $delayMs = 1000
    ) {
    }

    public function handle(MessageDispatcher $dispatcher): void
    {
        foreach ($this->logIds as $logId) {
            $log = MessageLog::query()->find($logId);

            if (! $log) {
                continue;
            }

            if ($log->scheduled_at && $log->scheduled_at->isFuture()) {
                continue;
            }

            $dispatcher->send($log);

            if ($this->delayMs > 0) {
                usleep($this->delayMs * 1000);
            }
        }
    }
}
