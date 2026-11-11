<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentQuestionReply extends Model
{
    protected $fillable = [
        'student_question_id',
        'user_id',
        'reply',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(
            StudentQuestion::class,
            'student_question_id'
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
