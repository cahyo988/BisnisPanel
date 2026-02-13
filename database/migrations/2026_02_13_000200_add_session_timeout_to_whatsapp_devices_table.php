<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->unsignedSmallInteger('auto_reply_session_timeout')
                ->default(30)
                ->after('auto_reply_menu')
                ->comment('Session timeout in minutes');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_devices', function (Blueprint $table) {
            $table->dropColumn('auto_reply_session_timeout');
        });
    }
};
