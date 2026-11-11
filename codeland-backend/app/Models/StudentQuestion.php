<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentQuestion extends Model
{
    protected $fillable = [
        'student_id',
        'group_id',
        'course_lesson_id',
        'title',
        'question',
        'status',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function courseLesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(StudentQuestionReply::class);
    }
}
