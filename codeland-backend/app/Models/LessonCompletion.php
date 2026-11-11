<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonCompletion extends Model
{
    protected $fillable = [
        'student_id',
        'course_lesson_id',
        'completed_at',
        'is_reviewed',
        'reviewed_by',
        'reviewed_at',
        'instructor_notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'is_reviewed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courseLesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
