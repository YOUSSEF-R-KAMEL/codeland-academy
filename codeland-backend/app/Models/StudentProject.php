<?php

namespace App\Models;

use App\Enums\StudentProjectStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProject extends Model
{
    protected $fillable = [
        'project_id',
        'student_id',
        'course_id',
        'group_id',
        'title',
        'description',
        'project_url',
        'status',
        'score',
        'instructor_feedback',
        'reviewed_by',
        'reviewed_at',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'status' => StudentProjectStatus::class,
            'score' => 'decimal:2',
            'reviewed_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
