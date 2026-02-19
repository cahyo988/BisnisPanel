<?php

namespace App\Console\Commands;

use App\Models\ChannelAccount;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Support\ContactKeyNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeDuplicateConversations extends Command
{
    protected $signature = 'inbox:merge-duplicate-conversations {--dry-run : Preview without writing changes} {--chunk=500 : Rows per chunk for orphan log backfill}';

    protected $description = 'Merge duplicate conversations by channel/account/normalized contact and backfill orphan logs';

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $chunk = max((int) $this->option('chunk'), 100);

        $summary = [
            'orphan_logs_assigned' => 0,
            'conversation_groups_merged' => 0,
            'duplicate_conversations_deleted' => 0,
            'logs_repointed' => 0,
        ];

        $this->info($isDryRun ? 'Running in dry-run mode.' : 'Running merge execution.');

        $summary['orphan_logs_assigned'] = $this->backfillOrphanLogs($chunk, $isDryRun);

        /** @var Collection<int, Conversation> $all */
        $all = Conversation::query()
            ->orderBy('channel')
            ->orderBy('channel_account_id')
            ->orderBy('id')
            ->get();

        $groups = $all->groupBy(function (Conversation $conversation): string {
            $normalized = ContactKeyNormalizer::normalizeForChannel($conversation->channel, $conversation->contact_key);
            $canonical = filled($normalized) ? $normalized : 'raw:'.trim($conversation->contact_key);

            return implode('|', [$conversation->channel, $conversation->channel_account_id, $canonical]);
        });

        foreach ($groups as $group) {
            if ($group->count() <= 1) {
                continue;
            }

            $primary = $this->pickPrimaryConversation($group);
            $duplicates = $group->where('id', '!=', $primary->id)->values();
            $normalized = ContactKeyNormalizer::normalizeForChannel($primary->channel, $primary->contact_key);
            $mergedUnread = (int) $group->sum('unread_count');

            $summary['conversation_groups_merged']++;

            if (! $isDryRun && filled($normalized) && $primary->contact_key !== $normalized) {
                $primary->update(['contact_key' => $normalized]);
            }

            foreach ($duplicates as $duplicate) {
                $normalizedDuplicate = ContactKeyNormalizer::normalizeForChannel($duplicate->channel, $duplicate->contact_key);

                if (! $isDryRun) {
                    DB::transaction(function () use ($primary, $duplicate, $normalizedDuplicate, &$summary): void {
                        $moved = MessageLog::query()
                            ->where('conversation_id', $duplicate->id)
                            ->update([
                                'conversation_id' => $primary->id,
                                'phone' => filled($normalizedDuplicate) ? $normalizedDuplicate : $primary->contact_key,
                            ]);

                        $summary['logs_repointed'] += $moved;

                        $duplicate->delete();
                        $summary['duplicate_conversations_deleted']++;
                    });
                }
            }

            if (! $isDryRun) {
                $this->refreshConversationSnapshot($primary->id, $mergedUnread);
            }
        }

        $this->line('---');
        $this->line('Merge Summary');
        foreach ($summary as $key => $value) {
            $this->line(sprintf('%s: %d', $key, $value));
        }

        return self::SUCCESS;
    }

    private function backfillOrphanLogs(int $chunk, bool $isDryRun): int
    {
        $assigned = 0;

        MessageLog::query()
            ->whereNotNull('channel_account_id')
            ->whereNull('conversation_id')
            ->orderBy('id')
            ->chunkById($chunk, function (Collection $logs) use (&$assigned, $isDryRun): void {
                foreach ($logs as $log) {
                    $account = ChannelAccount::query()->find($log->channel_account_id);

                    if (! $account) {
                        continue;
                    }

                    $normalized = ContactKeyNormalizer::normalizeForChannel($account->channel, $log->phone);
                    if (blank($normalized)) {
                        continue;
                    }

                    if (! $isDryRun) {
                        DB::transaction(function () use ($log, $account, $normalized): void {
                            $conversation = Conversation::query()->firstOrCreate(
                                [
                                    'channel' => $account->channel,
                                    'channel_account_id' => $account->id,
                                    'contact_key' => $normalized,
                                ],
                                [
                                    'user_id' => $log->user_id,
                                    'contact_name' => null,
                                    'unread_count' => 0,
                                ]
                            );

                            $log->update([
                                'conversation_id' => $conversation->id,
                                'phone' => $normalized,
                            ]);
                        });
                    }

                    $assigned++;
                }
            });

        return $assigned;
    }

    /**
     * @param  Collection<int, Conversation>  $group
     */
    private function pickPrimaryConversation(Collection $group): Conversation
    {
        return $group
            ->sortByDesc(function (Conversation $conversation): int {
                $count = MessageLog::query()->where('conversation_id', $conversation->id)->count();

                return ($count * 1000000000) - $conversation->id;
            })
            ->first();
    }

    private function refreshConversationSnapshot(int $conversationId, int $mergedUnread): void
    {
        $conversation = Conversation::query()->find($conversationId);

        if (! $conversation) {
            return;
        }

        $latest = MessageLog::query()
            ->where('conversation_id', $conversationId)
            ->latest('created_at')
            ->first();

        $lastIncoming = MessageLog::query()
            ->where('conversation_id', $conversationId)
            ->where('direction', MessageLog::DIRECTION_INCOMING)
            ->latest('created_at')
            ->first();

        $lastOutgoing = MessageLog::query()
            ->where('conversation_id', $conversationId)
            ->where('direction', MessageLog::DIRECTION_OUTGOING)
            ->latest('created_at')
            ->first();

        $conversation->update([
            'last_message_preview' => $latest?->message ? mb_substr((string) $latest->message, 0, 140) : $conversation->last_message_preview,
            'last_message_at' => $latest?->created_at,
            'last_incoming_at' => $lastIncoming?->created_at,
            'last_outgoing_at' => $lastOutgoing?->created_at,
            'unread_count' => $mergedUnread,
        ]);
    }
}
