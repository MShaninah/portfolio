<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ContactRequest;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final readonly class ContactService
{
    /**
     * The acknowledgement goes to an address a stranger typed in, so this
     * bucket is shared by every submission rather than keyed per visitor.
     */
    public const string GLOBAL_LIMITER_KEY = 'all-recipients';

    /**
     * Longest stretch of the sender's own message that is quoted back.
     *
     * The quote is a courtesy, not a transcript. Echoing all 5000 permitted
     * characters would turn the acknowledgement itself into a delivery
     * vehicle for whatever someone cares to paste in.
     */
    private const int QUOTE_MAX_CHARS = 1200;

    private const int NAME_MAX_CHARS = 60;

    private const int SUBJECT_MAX_CHARS = 180;

    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        #[Target('contact_ack_recipient')]
        private RateLimiterFactoryInterface $recipientLimiter,
        #[Target('contact_ack_global')]
        private RateLimiterFactoryInterface $globalLimiter,
        #[Autowire('%app.contact_to%')]
        private string $to,
        #[Autowire('%app.contact_from%')]
        private string $from,
        #[Autowire('%app.contact_verify_recipient_domain%')]
        private bool $verifyRecipientDomain,
    ) {
    }

    /**
     * Throttle bucket for one recipient.
     *
     * Hashed rather than the plain address: limiter keys are written into
     * cache entries on disk, and a visitor's email address does not belong
     * there. Public because the test suite has to clear the same bucket.
     */
    public static function recipientLimiterKey(string $email): string
    {
        return hash('sha256', mb_strtolower(trim($email)));
    }

    /**
     * Notifies the site owner, then acknowledges receipt to the sender.
     *
     * @throws TransportExceptionInterface if the owner notification fails
     */
    public function sendContactEmail(ContactRequest $dto): void
    {
        if (!$this->to || !$this->from) {
            throw new RuntimeException('Contact email parameters are not configured.');
        }

        $name = self::singleLine($dto->name, self::NAME_MAX_CHARS);
        $subject = self::singleLine($dto->subject, self::SUBJECT_MAX_CHARS);

        // The owner notification is the one that must not be lost, so it is
        // sent first and its failure propagates to the caller as a 503.
        $this->mailer->send(
            new Email()
                ->from($this->from)
                ->to($this->to)
                ->replyTo($dto->email)
                ->subject("[Portfolio] {$subject}")
                ->text("Name: {$name}\nEmail: {$dto->email}\n\n{$dto->message}")
        );

        $this->sendAcknowledgement($dto);
    }

    /**
     * Confirms to the sender that their message arrived.
     *
     * Deliberately best-effort: by the time this runs the enquiry has already
     * reached the owner, so a bounce here must not turn a delivered message
     * into an error for the visitor. It is logged instead.
     *
     * This is also the one place in the application that sends mail to an
     * address chosen by an anonymous caller, so the guards below exist to stop
     * the form being used as a way to put text in a stranger's inbox.
     */
    private function sendAcknowledgement(ContactRequest $dto): void
    {
        if (!$this->recipientLimiter->create(self::recipientLimiterKey($dto->email))->consume()->isAccepted()) {
            // Repeated submissions naming the same third party. Every message
            // still reached the owner; the third party gets one note, not a
            // full mailbox. Changing IP does not help, the key is the address.
            $this->logger->info('Contact acknowledgement throttled for this recipient.', [
                'recipient' => $dto->email,
            ]);

            return;
        }

        if (!$this->globalLimiter->create(self::GLOBAL_LIMITER_KEY)->consume()->isAccepted()) {
            // Per-recipient throttling does nothing against a flood spread
            // over many addresses from many IPs. This is the hard ceiling on
            // how much mail the form can emit, whoever asks for it.
            $this->logger->warning('Contact acknowledgements suspended: hourly ceiling reached.', [
                'recipient' => $dto->email,
            ]);

            return;
        }

        if ($this->verifyRecipientDomain && !self::domainAcceptsMail($dto->email)) {
            // A domain with no mail route can only produce a bounce, and
            // bounces are what cost a sending domain its reputation.
            $this->logger->info('Contact acknowledgement skipped: recipient domain has no mail route.', [
                'recipient' => $dto->email,
            ]);

            return;
        }

        // The body is plain text on purpose. It quotes back content the sender
        // supplied, and plain text removes any possibility of markup in that
        // content being interpreted by the recipient's mail client.
        $body = \sprintf(
            <<<'TEXT'
                Hi %s,

                thanks for getting in touch. Your message has arrived and I will get back to you shortly.

                For your records, this is what was sent from this address:

                %s

                --
                Mohamad Shaninah
                https://mshaninah.com

                Automated confirmation, sent once per address. If you did not fill in
                the contact form at https://mshaninah.com, someone entered your address
                there; nothing further will be sent and you can ignore this. Replies go
                to %s.
                TEXT,
            self::salutation($dto->name),
            self::quoted($dto),
            $this->to,
        );

        try {
            $this->mailer->send(
                new Email()
                    ->from($this->from)
                    ->to($dto->email)
                    // A reply to this acknowledgement should reach a human.
                    ->replyTo($this->to)
                    // Fixed, never the submitted subject: the subject line is
                    // the most visible part of a message, and it is not worth
                    // letting a stranger choose the one a victim would see.
                    ->subject('Thanks for your message')
                    ->text($body)
            );
        } catch (TransportExceptionInterface $e) {
            $this->logger->warning('Contact acknowledgement could not be sent.', [
                'exception' => $e,
                'recipient' => $dto->email,
            ]);
        }
    }

    /**
     * The sender's own words, rendered so they cannot be mistaken for ours.
     *
     * Every line carries a "> " prefix, which is what makes quoting safe at
     * all: without it a sender could write a paragraph that lands in a
     * stranger's inbox reading as though this domain had written it.
     */
    private static function quoted(ContactRequest $dto): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $dto->message);
        // Control characters, newlines excepted. A stray escape sequence has
        // no business in a mail body.
        $message = preg_replace('/(?!\n)\p{Cc}/u', '', $message) ?? $message;

        if (mb_strlen($message) > self::QUOTE_MAX_CHARS) {
            $message = mb_substr($message, 0, self::QUOTE_MAX_CHARS)."\n[truncated]";
        }

        $subject = self::singleLine($dto->subject, self::SUBJECT_MAX_CHARS);
        $block = '' === $subject ? $message : "Subject: {$subject}\n\n{$message}";

        return implode("\n", array_map(
            static fn (string $line): string => rtrim('> '.$line),
            explode("\n", $block),
        ));
    }

    private static function salutation(string $name): string
    {
        $name = self::singleLine($name, self::NAME_MAX_CHARS);

        return '' === $name ? 'there' : $name;
    }

    /**
     * Collapses a submitted field to a single line.
     *
     * A newline in the name is the whole point of this: left in, it would let
     * a sender break out of the salutation and write lines of their own
     * outside the quoted block, which is the one thing the "> " prefix is
     * there to prevent.
     *
     * Only genuine control characters are dropped. Format characters such as
     * ZWNJ or RLM survive, because they are part of how a great many names
     * are correctly written.
     */
    private static function singleLine(string $value, int $maxChars): string
    {
        $collapsed = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $collapsed = preg_replace('/\p{Cc}/u', '', $collapsed) ?? $collapsed;

        return mb_substr(trim($collapsed), 0, $maxChars);
    }

    /**
     * Whether mail to this address could be delivered at all.
     */
    private static function domainAcceptsMail(string $email): bool
    {
        $at = strrpos($email, '@');

        if (false === $at) {
            return false;
        }

        $domain = substr($email, $at + 1);

        // An A or AAAA record is enough on its own: a domain with no MX but a
        // host record still accepts mail at that host (RFC 5321 implicit MX).
        return checkdnsrr($domain, 'MX')
            || checkdnsrr($domain, 'A')
            || checkdnsrr($domain, 'AAAA');
    }
}
