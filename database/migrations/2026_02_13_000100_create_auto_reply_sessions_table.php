<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_reply_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_device_id')->constrained()->cascadeOnDelete();
            $table->string('sender_phone', 30);
            $table->string('current_menu_key', 80)->default('root');
            $table->boolean('greeted')->default(false);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique(['whatsapp_device_id', 'sender_phone'], 'session_device_sender_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_reply_sessions');
    }
};
