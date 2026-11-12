<?php

namespace App\Enums;

enum StudentProjectStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case SUBMITTED = 'submitted';
    case UNDER_REVIEW = 'under_review';
    case REVISION_REQUIRED = 'revision_required';
    case REVIEWED = 'reviewed';

    public function canStartReview(): bool
    {
        return $this === self::SUBMITTED;
    }

    public function canBeReviewed(): bool
    {
        return $this === self::UNDER_REVIEW;
    }

    public function canBeResubmitted(): bool
    {
        return $this === self::REVISION_REQUIRED;
    }

    public function canTransitionTo(self $nextStatus): bool
    {
        return match ($this) {
            self::IN_PROGRESS => $nextStatus === self::SUBMITTED,

            self::SUBMITTED => $nextStatus === self::UNDER_REVIEW,

            self::UNDER_REVIEW => in_array($nextStatus, [
                self::REVIEWED,
                self::REVISION_REQUIRED,
            ], true),

            self::REVISION_REQUIRED => $nextStatus === self::SUBMITTED,

            self::REVIEWED => false,
        };
    }
}
