<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Grouping for the skills section.
 *
 * Only the key lives in the database; the human-readable title and description
 * come from the frontend locale files, so they can be translated.
 */
enum SkillCategory: string
{
    case Frontend = 'frontend';
    case Backend = 'backend';
    case DataAi = 'data_ai';
    case DevOps = 'devops';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
