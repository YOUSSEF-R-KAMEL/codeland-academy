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
        'financial_summary',
        'is_overdue',
        'overdue_days',
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

    public function getFinancialSummaryAttribute(): array
    {
        $installments = $this->installments;

        return [
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount,
            'remaining_amount' => $this->remaining_amount,
            'installments_count' => $installments->count(),
            'paid_installments_count' => $installments
                ->where('status', 'paid')
                ->count(),
            'pending_installments_count' => $installments
                ->where('status', '!=', 'paid')
                ->count(),
            'overdue_installments_count' => $installments
                ->filter(fn($installment) => $installment->is_overdue)
                ->count(),

            'partially_paid_installments_count' => $installments
                ->where('status', 'partially_paid')
                ->count(),
        ];
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at !== null
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
}
