<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('story_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained('stories')->onDelete('cascade');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // image or video
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_attachments');
    }
};
