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
        Schema::create('breaking_news', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('alert_text');
            $table->string('url')->nullable();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('priority')->default(1)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('live_blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('summary')->nullable();
            $table->string('status')->default('live')->index();
            $table->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cover_image')->nullable();
            $table->boolean('is_live')->default(true)->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });

        Schema::create('live_blog_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('live_blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->longText('content');
            $table->string('image_path')->nullable();
            $table->boolean('is_pinned')->default(false)->index();
            $table->timestamp('published_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_blog_updates');
        Schema::dropIfExists('live_blogs');
        Schema::dropIfExists('breaking_news');
    }
};
