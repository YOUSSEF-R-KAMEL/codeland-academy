<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentInstallment extends Model
{
    protected $fillable = [
        'invoice_id',
        'installment_number',
        'amount',
        'due_at',
        'paid_at',
        'status',
        'notes',
    ];

    protected $appends = [
        'remaining_amount',
        'is_overdue',
        'overdue_days',
        'paid_amount',
    ];

    protected function casts(): array
    {
        return [
            'installment_number' => 'integer',
            'amount' => 'decimal:2',
            'due_at' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getRemainingAmountAttribute(): string
    {
        $amountInCents = (int) round((float) $this->amount * 100);

        $paidAmount = $this->relationLoaded('payments')
            ? $this->payments->where('status', 'confirmed')->sum('amount')
            : $this->payments()
                ->where('status', 'confirmed')
                ->sum('amount');

        $paidInCents = (int) round((float) $paidAmount * 100);

        $remainingInCents = max(0, $amountInCents - $paidInCents);

        return number_format($remainingInCents / 100, 2, '.', '');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'paid'
            && $this->due_at !== null
            && $this->due_at->lt(now()->startOfDay())
            && (float) $this->remaining_amount > 0;
    }

    public function getOverdueDaysAttribute(): int
    {
        if (!$this->is_overdue) {
            return 0;
        }

        return (int) $this->due_at->startOfDay()->diffInDays(
            now()->startOfDay()
        );
    }
    public function getPaidAmountAttribute(): string
    {
        $paidAmount = $this->relationLoaded('payments')
            ? $this->payments->where('status', 'confirmed')->sum('amount')
            : $this->payments()
                ->where('status', 'confirmed')
                ->sum('amount');

        return number_format((float) $paidAmount, 2, '.', '');
    }
}
