<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseLevel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'title',
        'slug',
        'description',
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

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(CourseLesson::class);
    }


    public function groups(): HasMany
    {
        return $this->hasMany(Group::class);
    }

    public function studentProjects(): HasMany
    {
        return $this->hasMany(StudentProject::class);
    }

    public function studentProgress(): HasMany
    {
        return $this->hasMany(StudentProgress::class);
    }

    public function studentEvaluations(): HasMany
    {
        return $this->hasMany(StudentEvaluation::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'course_level_id');
    }
}
