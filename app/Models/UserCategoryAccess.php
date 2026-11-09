<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCategoryAccess extends Model
{
    use HasFactory;

    protected $table = 'user_category_access';

    protected $fillable = [
        'user_id',
        'category_id',
        'can_approve',
        'can_publish',
    ];

    protected function casts(): array
    {
        return [
            'can_approve' => 'boolean',
            'can_publish' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
