<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('raw_payload');
            $table->timestamp('sent_at')->nullable()->after('scheduled_at');
            $table->timestamp('delivered_at')->nullable()->after('sent_at');
            $table->timestamp('read_at')->nullable()->after('delivered_at');
            $table->string('gateway_message_id')->nullable()->after('read_at');
        });
    }

    public function down(): void
    {
        Schema::table('message_logs', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'sent_at', 'delivered_at', 'read_at', 'gateway_message_id']);
        });
    }
};
