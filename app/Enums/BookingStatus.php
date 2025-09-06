<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public function canTransitionTo(BookingStatus $status): bool
    {
        return match($this) {
            self::PENDING => in_array($status, [self::CONFIRMED, self::CANCELLED]),
            self::CONFIRMED => in_array($status, [self::IN_PROGRESS, self::CANCELLED, self::NO_SHOW]),
            self::IN_PROGRESS => in_array($status, [self::COMPLETED, self::CANCELLED]),
            default => false,
        };
    }
}
