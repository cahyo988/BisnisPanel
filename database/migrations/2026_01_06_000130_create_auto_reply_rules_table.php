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
        Schema::create('auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('whatsapp_device_id')->constrained()->cascadeOnDelete();
            $table->string('keyword');
            $table->enum('match_mode', ['exact', 'contains'])->default('exact');
            $table->enum('reply_type', ['text', 'template'])->default('text');
            $table->text('reply_text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['whatsapp_device_id', 'keyword', 'match_mode'], 'auto_reply_unique_keyword');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auto_reply_rules');
    }
};

