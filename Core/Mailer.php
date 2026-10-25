<?php

declare(strict_types=1);

namespace Ideal\Core;

use Symfony\Component\Mime\Address;
use Ideal\Core\Exception\MailerException;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class Mailer
{
    private SymfonyMailer $mailer;
    private Email $email;

    public function __construct()
    {
        $config = Config::getInstance();
        $dsn = sprintf(
            'smtp://%s:%s@%s:%d',
            urlencode($config->smtp['user']),
            urlencode($config->smtp['password']),
            $config->smtp['server'],
            $config->smtp['port'],
        );
        $this->mailer = new SymfonyMailer(Transport::fromDsn($dsn));
        $this->email = new Email();
        $this->from($config->robotEmail);
        if ($config->mailBcc !== '') {
            $this->bcc($config->mailBcc);
        }
    }

    public function from(Address|string $from): self
    {
        $this->email->from($from);

        return $this;
    }

    public function to(Address|string ...$to): self
    {
        $this->email->to(...$to);

        return $this;
    }

    public function bcc(string $bcc): self
    {
        $this->email->bcc($bcc);

        return $this;
    }

    public function subject(string $subject): self
    {
        $this->email->subject($subject);

        return $this;
    }

    public function text(string $text): self
    {
        $this->email->text($text);

        return $this;
    }

    public function html(string $html): self
    {
        $this->email->html($html);

        return $this;
    }

    /**
     * @throws MailerException
     */
    public function send(): void
    {
        try {
            $this->mailer->send($this->email);
        } catch (\Throwable $throwable) {
            throw new MailerException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    public function attach(string $path, ?string $name = null, ?string $contentType = null): self
    {
        $this->email->attachFromPath($path, $name, $contentType);

        return $this;
    }
}
