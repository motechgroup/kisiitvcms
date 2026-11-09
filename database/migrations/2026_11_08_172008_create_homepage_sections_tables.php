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
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('section_type')->index();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('display_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('article_count')->default(6);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('homepage_section_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homepage_section_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->integer('display_order')->default(0)->index();
            $table->timestamps();

            $table->unique(['homepage_section_id', 'article_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_section_articles');
        Schema::dropIfExists('homepage_sections');
    }
};
