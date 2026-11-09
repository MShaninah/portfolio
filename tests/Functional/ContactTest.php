<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\ResetsRateLimiters;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;

final class ContactTest extends WebTestCase
{
    use ResetsRateLimiters;

    private ?KernelBrowser $client = null;

    /**
     * One client per test, with a per-test client IP: the rate limiter is keyed
     * by IP and its state outlives a single request, so sharing an IP between
     * tests would make them order-dependent.
     */
    private function client(string $ip): KernelBrowser
    {
        if (null !== $this->client) {
            return $this->client;
        }

        $client = static::createClient([], ['REMOTE_ADDR' => $ip]);
        $this->resetRateLimiters($ip);

        return $this->client = $client;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $ip, array $payload): KernelBrowser
    {
        $client = $this->client($ip);
        $client->request(
            'POST',
            '/api/contact',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, \JSON_THROW_ON_ERROR),
        );

        return $client;
    }

    /**
     * @return array<string, string>
     */
    private static function validPayload(): array
    {
        return [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.test',
            'subject' => 'Hello',
            'message' => 'This message is comfortably longer than ten characters.',
        ];
    }

    public function testAValidSubmissionIsAccepted(): void
    {
        $client = $this->post('198.51.100.1', self::validPayload());

        self::assertResponseIsSuccessful();
        self::assertSame(['message' => 'Sent'], json_decode((string) $client->getResponse()->getContent(), true));
    }

    public function testAFilledHoneypotIsSilentlyDiscarded(): void
    {
        // The bot must receive the same answer a human would, and no mail may
        // be sent.
        $client = $this->post('198.51.100.2', self::validPayload() + ['company' => 'spam-bot inc']);

        self::assertResponseIsSuccessful();
        self::assertSame(['message' => 'Sent'], json_decode((string) $client->getResponse()->getContent(), true));
        self::assertQueuedEmailCount(0);
        self::assertEmailCount(0);
    }

    public function testAnEmptyHoneypotStillSendsMail(): void
    {
        $client = $this->post('198.51.100.3', self::validPayload() + ['company' => '   ']);

        self::assertResponseIsSuccessful();
        self::assertEmailCount(1);
    }

    public function testMailIsSentSynchronouslySoFailuresCanBeReported(): void
    {
        // Previously SendEmailMessage was routed to the async transport, so the
        // endpoint reported success while the mail merely sat in a queue.
        $this->post('198.51.100.4', self::validPayload());

        self::assertEmailCount(1);
        self::assertQueuedEmailCount(0);
    }

    public function testTheReplyToIsTheSubmittedAddress(): void
    {
        $this->post('198.51.100.5', self::validPayload());

        $email = self::getMailerMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('[Portfolio] Hello', $email->getSubject());
        self::assertSame('ada@example.test', $email->getReplyTo()[0]->getAddress());
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidPayloads(): iterable
    {
        yield 'missing name' => [['email' => 'a@b.test', 'subject' => 's', 'message' => 'long enough message']];
        yield 'invalid email' => [['name' => 'A', 'email' => 'not-an-email', 'subject' => 's', 'message' => 'long enough message']];
        yield 'message too short' => [['name' => 'A', 'email' => 'a@b.test', 'subject' => 's', 'message' => 'short']];
        yield 'empty object' => [[]];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidPayloads')]
    public function testInvalidSubmissionsAreRejected(array $payload): void
    {
        $this->post('198.51.100.6', $payload);

        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        self::assertEmailCount(0);
    }

    public function testSubmissionsAreRateLimited(): void
    {
        $ip = '198.51.100.7';

        for ($i = 0; $i < 5; ++$i) {
            $this->post($ip, self::validPayload());
        }

        $client = $this->post($ip, self::validPayload());

        self::assertResponseStatusCodeSame(Response::HTTP_TOO_MANY_REQUESTS);
        self::assertResponseHasHeader('Retry-After');
    }
}
