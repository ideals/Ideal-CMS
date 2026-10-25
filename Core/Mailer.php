<?php

declare(strict_types=1);

namespace Ideal\Core;

use Ideal\Spider\Exception\MailTransportException;
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
        $this->from($config->smtp['email']);
        if ($config->smtp['bcc'] !== '') {
            $this->bcc($config->smtp['bcc']);
        }
    }

    public function from(string $from): self
    {
        $this->email->from($from);

        return $this;
    }

    public function to(string|array $to): self
    {
        $to = is_string($to) ? [$to] : $to;
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
     * @throws MailTransportException
     */
    public function sent(): void
    {
        try {
            $this->mailer->send($this->email);
        } catch (\Throwable $throwable) {
            throw new MailTransportException($throwable->getMessage(), $throwable->getCode(), $throwable);
        }
    }

    public function fileAttach(string $path, string $type, string $name): self
    {
        $this->email->attachFromPath($path, $name, $type); // 'application/pdf'

        return $this;
    }
}
