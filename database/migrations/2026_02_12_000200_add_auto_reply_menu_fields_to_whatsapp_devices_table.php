<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->text('auto_reply_greeting')->nullable()->after('session');
            $table->json('auto_reply_menu')->nullable()->after('auto_reply_greeting');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->dropColumn(['auto_reply_greeting', 'auto_reply_menu']);
        });
    }
};
