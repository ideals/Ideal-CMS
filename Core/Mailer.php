<?php

declare(strict_types=1);

namespace Ideal\Core;

use Ideal\Core\Exception\MailerException;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class Mailer
{
    private array $smtp;
    private array $fileAttach = [];
    private string $from;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->smtp = $config->smtp;
        $this->from = $this->smtp['user'];
    }

    /**
     * @throws MailerException
     */
    public function send($to, $title, $plain = null, $html = null): void
    {
        // Формируем DSN для SMTP-сервера
        // Формат: smtp://username:password@host:port
        $dsn = sprintf(
            'smtp://%s:%s@%s:%d',
            urlencode($this->smtp['user']),
            urlencode($this->smtp['password']),
            $this->smtp['server'],
            $this->smtp['port'],
        );


        $transport = Transport::fromDsn($dsn);
        $mailer = new SymfonyMailer($transport);

        $email = (new Email())
            ->from($this->from)
            ->to($to)
            ->bcc('top@neox.ru')
            ->subject($title)
        ;

        foreach ($this->fileAttach as $attach) {
            $email->attachFromPath(
                $attach['path'],
                $attach['name'] ?? null,
                $attach['type'] ?? null,
            );
        }


        if (!empty($plain)) {
            $email->text($plain);
        }

        if (!empty($html)) {
            $email->html($html);
        }

        try {
            $mailer->send($email);
        } catch (\Throwable $exception) {
            throw new MailerException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function attach(string $path, ?string $name = null, ?string $contentType = null): self
    {
        $this->fileAttach[] = [
            'path' => $path,
            'name' => $name,
            'type' => $contentType,
        ];

        return $this;
    }
}
