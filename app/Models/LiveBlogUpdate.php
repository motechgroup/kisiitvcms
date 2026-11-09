<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveBlogUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_blog_id',
        'user_id',
        'title',
        'content',
        'image_path',
        'is_pinned',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function liveBlog(): BelongsTo
    {
        return $this->belongsTo(LiveBlog::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
