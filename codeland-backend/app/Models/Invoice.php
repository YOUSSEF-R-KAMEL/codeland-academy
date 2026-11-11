<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'enrollment_id',
        'invoice_number',
        'total_amount',
        'payment_plan',
        'paid_amount',
        'currency',
        'status',
        'issued_at',
        'due_at',
        'notes',
    ];

    protected $appends = [
        'remaining_amount',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'issued_at' => 'date',
            'due_at' => 'date',
        ];
    }

    public function getRemainingAmountAttribute(): string
    {
        $totalInCents = (int) round((float) $this->total_amount * 100);
        $paidInCents = (int) round((float) $this->paid_amount * 100);

        $remainingInCents = max(0, $totalInCents - $paidInCents);

        return number_format($remainingInCents / 100, 2, '.', '');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentInstallment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
