<?php

declare(strict_types=1);

namespace App\Tests;

use LogicException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Rate limiter state lives in the application cache, so it survives both the
 * end of a test and the end of a whole PHPUnit run. Tests that exercise
 * throttling must therefore start from a known state, or they pass once and
 * fail on the next run.
 */
trait ResetsRateLimiters
{
    private function resetRateLimiters(string $ip): void
    {
        $container = static::getContainer();

        foreach (['limiter.admin_login', 'limiter.contact_form'] as $id) {
            $factory = $container->get($id);

            if (!$factory instanceof RateLimiterFactoryInterface) {
                throw new LogicException(\sprintf('Service "%s" is not a rate limiter factory.', $id));
            }

            $factory->create($ip)->reset();
        }
    }
}
