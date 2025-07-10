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
        Schema::create('user_profiles', function (Blueprint $table) {
             $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('gender')->nullable();
            $table->date('born_date')->nullable();

            $table->foreignId('country_id')->nullable()->constrained('countries');
            $table->foreignId('qualification_id')->nullable()->constrained('qualifications');
            $table->foreignId('employment_status_id')->nullable()->constrained('employment_statuses');
            $table->foreignId('preferred_work_style_id')->nullable()->constrained('work_styles');
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('sub_category_id')->nullable()->constrained('sub_categories');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
