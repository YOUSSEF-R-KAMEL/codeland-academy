<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEvaluation extends Model
{
    protected $fillable = [
        'student_id',
        'course_id',
        'instructor_id',
        'course_rating',
        'course_comment',
        'instructor_rating',
        'instructor_comment',
    ];

    protected function casts(): array
    {
        return [
            'course_rating' => 'integer',
            'instructor_rating' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
