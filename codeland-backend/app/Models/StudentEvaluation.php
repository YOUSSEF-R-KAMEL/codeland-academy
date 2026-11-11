<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEvaluation extends Model
{
    protected $fillable = [
        'student_id',
        'course_level_id',
        'group_id',
        'instructor_id',
        'understanding_score',
        'problem_solving_score',
        'participation_score',
        'practical_work_score',
        'overall_score',
        'strengths',
        'areas_to_improve',
        'instructor_notes',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'understanding_score' => 'integer',
            'problem_solving_score' => 'integer',
            'participation_score' => 'integer',
            'practical_work_score' => 'integer',
            'overall_score' => 'decimal:2',
            'evaluated_at' => 'date',
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

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
