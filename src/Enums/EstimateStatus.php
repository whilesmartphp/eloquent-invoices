<?php

namespace Whilesmart\Invoices\Enums;

enum EstimateStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Expired = 'expired';

    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
