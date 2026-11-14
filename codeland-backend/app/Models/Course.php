<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'thumbnail_url',
        'duration_weeks',
        'sessions_per_week',
        'session_duration_minutes',
        'monthly_price',
        'full_price',
        'currency',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'duration_weeks' => 'integer',
            'sessions_per_week' => 'integer',
            'session_duration_minutes' => 'integer',
            'monthly_price' => 'decimal:2',
            'full_price' => 'decimal:2',
            'is_published' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }
}
