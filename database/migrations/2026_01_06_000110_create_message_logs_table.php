<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_device_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('batch_id')->nullable()->index();
            $table->string('direction', 20);
            $table->string('type', 20)->default('text');
            $table->string('phone');
            $table->longText('message')->nullable();
            $table->string('status', 20)->default('queued');
            $table->string('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['phone', 'direction']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
