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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->string('featured_image')->nullable();
            $table->string('image_caption')->nullable();
            $table->string('image_credit')->nullable();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('article_type')->default('standard')->index();
            $table->string('status')->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_breaking')->default(false)->index();
            $table->timestamp('breaking_until')->nullable();
            $table->boolean('is_editors_pick')->default(false)->index();
            $table->unsignedBigInteger('view_count')->default(0)->index();
            $table->unsignedInteger('reading_time_minutes')->default(3);
            $table->string('video_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url')->nullable();
            $table->boolean('allow_comments')->default(true);
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['category_id', 'status', 'published_at']);
        });

        Schema::create('article_authors', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('co-author');
            $table->timestamps();

            $table->primary(['article_id', 'user_id']);
        });

        Schema::create('article_tag', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();

            $table->primary(['article_id', 'tag_id']);
        });

        Schema::create('article_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->integer('display_order')->default(0);
            $table->timestamps();
        });

        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('summary')->nullable();
            $table->longText('content');
            $table->text('notes')->nullable();
            $table->unsignedInteger('revision_number')->default(1);
            $table->timestamps();
        });

        Schema::create('editorial_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('action');
            $table->string('status_from')->nullable();
            $table->string('status_to');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editorial_reviews');
        Schema::dropIfExists('article_revisions');
        Schema::dropIfExists('article_media');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_authors');
        Schema::dropIfExists('articles');
    }
};
