<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonResource extends Model
{
    protected $fillable = [
        'course_lesson_id',
        'title',
        'description',
        'type',
        'url',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function courseLesson(): BelongsTo
    {
        return $this->belongsTo(CourseLesson::class);
    }
}
