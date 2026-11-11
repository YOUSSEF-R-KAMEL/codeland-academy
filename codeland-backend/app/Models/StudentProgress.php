<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProgress extends Model
{
    protected $table = 'student_progress';

    protected $fillable = [
        'student_id',
        'course_level_id',
        'completed_lessons',
        'total_lessons',
        'progress_percentage',
        'status',
        'last_activity_at',
        'instructor_notes',
    ];

    protected function casts(): array
    {
        return [
            'completed_lessons' => 'integer',
            'total_lessons' => 'integer',
            'progress_percentage' => 'decimal:2',
            'last_activity_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function courseLevel(): BelongsTo
    {
        return $this->belongsTo(CourseLevel::class);
    }
}
