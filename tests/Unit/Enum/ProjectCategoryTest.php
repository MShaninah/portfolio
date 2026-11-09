<?php

declare(strict_types=1);

namespace App\Tests\Unit\Enum;

use App\Enum\ProjectCategory;
use PHPUnit\Framework\TestCase;

final class ProjectCategoryTest extends TestCase
{
    public function testValuesMatchTheEnumCases(): void
    {
        self::assertSame(['Backend', 'AI', 'Enterprise', 'IoT'], ProjectCategory::values());
    }

    public function testEveryCaseIsOfferedAsAChoice(): void
    {
        foreach (ProjectCategory::cases() as $case) {
            self::assertContains($case->value, ProjectCategory::values());
        }
    }
}
