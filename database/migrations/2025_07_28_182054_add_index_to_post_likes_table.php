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
        Schema::table('post_likes', function (Blueprint $table) {
            Schema::table('post_likes', function (Blueprint $table) {
                $table->index(['user_id', 'post_id'], 'idx_post_likes_user_post');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_likes', function (Blueprint $table) {
           $table->dropIndex('idx_post_likes_user_post');
        });
    }
};
