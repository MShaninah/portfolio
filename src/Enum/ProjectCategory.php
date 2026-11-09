<?php

declare(strict_types=1);

namespace App\Enum;

enum ProjectCategory: string
{
    case Backend = 'Backend';
    case Ai = 'AI';
    case Enterprise = 'Enterprise';
    case Iot = 'IoT';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
