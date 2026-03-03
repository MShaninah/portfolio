<?php
declare(strict_types=1);

namespace App\Service;

use App\Dto\ContactRequest;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final readonly class ContactService
{
    public function __construct(
        private MailerInterface                      $mailer,
        #[Autowire('%contact_to%')] private string   $to,
        #[Autowire('%contact_from%')] private string $from,
    ) {}

    /**
     * @throws TransportExceptionInterface
     */
    public function sendContactEmail(ContactRequest $dto): void
    {
        if (!$this->to || !$this->from) {
            throw new \RuntimeException('Contact email parameters are not configured.');
        }

        $email = new Email()
            ->from($this->from)
            ->to($this->to)
            ->replyTo($dto->email)
            ->subject("[Portfolio] {$dto->subject}")
            ->text("Name: {$dto->name}\nEmail: {$dto->email}\n\n{$dto->message}");

        $this->mailer->send($email);
    }
}
