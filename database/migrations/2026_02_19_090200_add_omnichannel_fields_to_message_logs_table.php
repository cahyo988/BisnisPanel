<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->string('channel', 30)->default('whatsapp')->after('user_id');
            $table->foreignId('channel_account_id')->nullable()->after('channel')->constrained('channel_accounts')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->after('channel_account_id')->constrained()->nullOnDelete();
            $table->string('external_message_id')->nullable()->after('gateway_message_id');

            $table->index(['channel', 'created_at']);
            $table->index(['channel_account_id', 'created_at']);
            $table->index(['conversation_id', 'created_at']);
            $table->index(['channel', 'status']);
            $table->index(['external_message_id']);
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropIndex(['channel', 'created_at']);
            $table->dropIndex(['channel_account_id', 'created_at']);
            $table->dropIndex(['conversation_id', 'created_at']);
            $table->dropIndex(['channel', 'status']);
            $table->dropIndex(['external_message_id']);

            $table->dropConstrainedForeignId('conversation_id');
            $table->dropConstrainedForeignId('channel_account_id');
            $table->dropColumn(['channel', 'external_message_id']);
        });
    }
};
