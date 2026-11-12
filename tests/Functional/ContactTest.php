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
    private function client(string $ip, ?string $recipient): KernelBrowser
    {
        if (null !== $this->client) {
            return $this->client;
        }

        $client = static::createClient([], ['REMOTE_ADDR' => $ip]);
        $this->resetRateLimiters($ip, ...array_filter([$recipient]));

        return $this->client = $client;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function post(string $ip, array $payload): KernelBrowser
    {
        $email = $payload['email'] ?? null;
        $client = $this->client($ip, \is_string($email) ? $email : null);
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
        // One notification to the owner, one acknowledgement to the sender.
        self::assertEmailCount(2);
    }

    public function testMailIsSentSynchronouslySoFailuresCanBeReported(): void
    {
        // Previously SendEmailMessage was routed to the async transport, so the
        // endpoint reported success while the mail merely sat in a queue.
        $this->post('198.51.100.4', self::validPayload());

        self::assertEmailCount(2);
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

    public function testTheSenderGetsAnAcknowledgement(): void
    {
        $this->post('198.51.100.8', self::validPayload());

        self::assertResponseIsSuccessful();
        self::assertEmailCount(2);

        // Index 0 is the owner notification; index 1 is the acknowledgement.
        $ack = self::getMailerMessage(1);
        self::assertInstanceOf(Email::class, $ack);

        self::assertSame('ada@example.test', $ack->getTo()[0]->getAddress(), 'goes back to the sender');
        self::assertSame('no-reply@example.test', $ack->getFrom()[0]->getAddress());
        // A reply to the acknowledgement must reach a human, not the no-reply box.
        self::assertSame('contact@example.test', $ack->getReplyTo()[0]->getAddress());

        $body = (string) $ack->getTextBody();
        self::assertStringContainsString('Ada Lovelace', $body, 'addresses the sender by name');
        self::assertStringContainsString(
            '> This message is comfortably longer than ten characters.',
            $body,
            'quotes the message back, marked as a quote',
        );
        self::assertNotNull($ack->getTextBody());
        self::assertNull($ack->getHtmlBody(), 'plain text only: the body quotes sender-supplied content');
    }

    public function testTheAcknowledgementSubjectIsFixed(): void
    {
        // The subject line is the most visible part of a message. A stranger
        // typing an address into the form must not get to choose the one the
        // owner of that address sees.
        $this->post('198.51.100.13', ['subject' => 'Urgent: verify your account'] + self::validPayload());

        $ack = self::getMailerMessage(1);
        self::assertInstanceOf(Email::class, $ack);
        self::assertSame('Thanks for your message', $ack->getSubject());
    }

    public function testTheSendersWordsCannotMasqueradeAsOurs(): void
    {
        $this->post('198.51.100.11', [
            'name' => "Eve\nYour account has been suspended.",
            'email' => 'eve@example.test',
            'subject' => "Hello\nUrgent: act now",
            'message' => "First line.\nSecond line.",
        ]);

        $ack = self::getMailerMessage(1);
        self::assertInstanceOf(Email::class, $ack);

        $body = (string) $ack->getTextBody();

        // A newline in the name would otherwise let a sender write a line of
        // their own outside the quoted block, where it reads as ours.
        self::assertStringContainsString('Hi Eve Your account has been suspended.,', $body);

        // Everything the sender wrote carries a quote marker.
        self::assertStringContainsString('> Subject: Hello Urgent: act now', $body);
        self::assertStringContainsString('> First line.', $body);
        self::assertStringContainsString('> Second line.', $body);
        self::assertStringNotContainsString("\nSecond line.", $body, 'no unmarked line of their text');
    }

    public function testAnOverlongMessageIsTruncatedInTheAcknowledgement(): void
    {
        $long = str_repeat('A', 4000);
        $this->post('198.51.100.12', ['message' => $long] + self::validPayload());

        $owner = self::getMailerMessage(0);
        self::assertInstanceOf(Email::class, $owner);
        self::assertStringContainsString($long, (string) $owner->getTextBody(), 'the owner sees all of it');

        $ack = self::getMailerMessage(1);
        self::assertInstanceOf(Email::class, $ack);
        $body = (string) $ack->getTextBody();
        self::assertStringContainsString('> [truncated]', $body);
        self::assertLessThan(2000, mb_strlen($body), 'the acknowledgement is not a delivery vehicle');
    }

    public function testOnlyOneAcknowledgementReachesAGivenAddress(): void
    {
        $this->post('198.51.100.10', self::validPayload());
        self::assertEmailCount(2);

        // Same address again. The owner must still be notified, but the
        // address itself is not mailed twice - this is what stops the form
        // being used to fill a third party's inbox.
        $this->post('198.51.100.10', self::validPayload());
        self::assertEmailCount(1, message: 'owner notified; the sender is not mailed again');
    }

    public function testNoAcknowledgementWhenTheHoneypotIsTriggered(): void
    {
        $this->post('198.51.100.9', self::validPayload() + ['company' => 'spam-bot inc']);

        self::assertResponseIsSuccessful();
        // A bot must not be able to use the form to mail an arbitrary address.
        self::assertEmailCount(0);
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
