<?php

declare(strict_types=1);

namespace App\Tests;

use App\Service\ContactService;
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
    /**
     * @param string ...$recipients addresses whose acknowledgement throttle
     *                              should also be cleared. Needed by any test
     *                              that expects an acknowledgement, because
     *                              that limiter is keyed by address rather
     *                              than by IP and one per hour is all it
     *                              allows.
     */
    private function resetRateLimiters(string $ip, string ...$recipients): void
    {
        foreach (['limiter.admin_login', 'limiter.contact_form'] as $id) {
            $this->limiterFactory($id)->create($ip)->reset();
        }

        $this->limiterFactory('limiter.contact_ack_global')
            ->create(ContactService::GLOBAL_LIMITER_KEY)
            ->reset();

        foreach ($recipients as $recipient) {
            $this->limiterFactory('limiter.contact_ack_recipient')
                ->create(ContactService::recipientLimiterKey($recipient))
                ->reset();
        }
    }

    private function limiterFactory(string $id): RateLimiterFactoryInterface
    {
        $factory = static::getContainer()->get($id);

        if (!$factory instanceof RateLimiterFactoryInterface) {
            throw new LogicException(\sprintf('Service "%s" is not a rate limiter factory.', $id));
        }

        return $factory;
    }
}
