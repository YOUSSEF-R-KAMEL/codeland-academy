<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentConsent extends Model
{
    protected $fillable = [
        'student_id',
        'consenter_name',
        'consenter_contact',
        'consent_type',
        'status',
        'consented_at',
        'revoked_at',
        'document_version',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
