<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Services\MessageDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $messageLogId,
        private readonly array $options = []
    ) {
    }

    public function handle(MessageDispatcher $dispatcher): void
    {
        $log = MessageLog::query()->find($this->messageLogId);

        if (! $log) {
            return;
        }

        $dispatcher->send($log, $this->options);
    }
}

