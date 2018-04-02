<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Mail\Adapter;

use Ideal\Core\Db;

class Yandex
{
    // Устанавливаем данные для связи с почтой
    protected $email = '';
    protected $emailPassword = '';
    protected $startTime;
    protected $table = 'i_ideal_structure_mail';

    /**
     * Yandex constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->startTime = time();

        $this->connection = imap_open(
            '{imap.yandex.ru:993/imap/ssl}INBOX',
            $this->email,
            $this->emailPassword
        );

        if (!$this->connection) {
            throw new \Exception("Не удалось соединиться с почтовым сервером");
        }
    }

    public function loadMail()
    {
        $db = Db::getInstance();

        // Получаем последнее записанное в базу сообщение
        $lastMailMessage = $db->select('SELECT UID FROM ' . $this->table . ' ORDER BY UID DESC LIMIT 1');
        $mailUid = 0;
        if ($lastMailMessage) {
            $mailUid = $lastMailMessage[0]['UID'];
        }

        echo "Начинаем сбор данных с почтового сервера\n";

        // Получаем количество сообщений на почте
        $msgNum = imap_num_msg($this->connection);

        $i = 1;
        if ($mailUid) {
            // Получаем номер сообщения по заданному UID
            $i = imap_msgno($this->connection, $mailUid);
            $i++;
        }

        while ($i <= $msgNum) {
            // Даём скрипту 45 секунд на работу, потому что иногда ответ от сервера приходится ждать долго
            if ($this->startTime + 45 <= time()) {
                echo "Выход по таймауту, следует продолжить сбор данных\n";
                exit;
            }
            // Получаем структуру сообщения
            $msgStructure = imap_fetchstructure($this->connection, $i);

            // Получаем заголовки сообщения
            $headersInfo = imap_headerinfo($this->connection, $i);

            // Получаем отправителя
            $from = $headersInfo->from[0]->mailbox . '@' . $headersInfo->from[0]->host;

            // Получаем тему письма
            // А вот таким образом, потому что до версии 5.6 функция iconv_mime_decode работает не корректно
            $subjectDecode = imap_mime_header_decode($headersInfo->subject);
            $subject = '';
            foreach ($subjectDecode as $subjectDecodeElement) {
                $subject .= $subjectDecodeElement->text;
            }

            $msgPart = 1;
            $encoding = 0;
            $attachedFiles = '';
            // Письмо состоит из нескольких частей
            if ($msgStructure->type && isset($msgStructure->parts)) {
                // Обходим каждую часть
                $this->findPart($msgStructure->parts, $msgPart, $encoding, $attachedFiles);
            } else {
                $encoding = $msgStructure->encoding;
            }

            // Получаем дату получения письма на почте
            if ($headersInfo->MailDate) {
                $date = \DateTime::createFromFormat('d-M-Y H:i:s O', $headersInfo->MailDate);
            } else {
                // Если даты нет, то ставим сегодняшнюю.
                $date = new \DateTime();
            }

            // Получаем тело письма
            $body = imap_qprint(imap_fetchbody($this->connection, $i, $msgPart));

            if ($encoding == 3) {
                $body = base64_decode($body);
            }

            // Если после всех манипуляций тело письма так и не удалось получить, то не записываем такое сообщение
            if ($body) {
                $params = array(
                    'prev_structure' => '3-6',
                    'UID' => imap_uid($this->connection, $i),
                    'subject' => $subject,
                    'from' => $from,
                    'date_received' => $date->getTimestamp(),
                    'body' => $body,
                    'attachment' => $attachedFiles,
                );
                $id = $db->insert($this->table, $params);
            }
            $i++;
        }

        echo "Сбор данных окончен\n";
    }

    /**
     * Обходит все части письма для поиска вложений и текста
     *
     * @param stdClass $parts - Части письма
     * @param int $msgPart - Секция письма из которой необходимо считать текст
     * @param int $encoding - Код кодировки
     * @param string $attachedFiles - Строка со списком прикреплённых файлов через ";"
     */
    protected function findPart($parts, &$msgPart, &$encoding, &$attachedFiles)
    {
        $upPart = true;
        foreach ($parts as $part) {
            if (isset($part->disposition) &&
                $part->disposition == 'attachment' &&
                isset($part->dparameters) &&
                isset($part->dparameters[0]) &&
                isset($part->dparameters[0]->attribute) &&
                isset($part->dparameters[0]->value)
            ) {
                $attachedFiles .= $attachedFiles != '' ? ';' : '';
                $attachedFiles .= $part->dparameters[0]->value;
            }
            if ($part->type && isset($part->parts)) {
                findPart($part->parts, $msgPart, $encoding, $attachedFiles);
            }
            if ($part->type == 0 && $upPart) {
                $encoding = $part->encoding;
                $upPart = false;
            }
            if ($upPart) {
                $msgPart++;
            }
        }
    }

    public function __destruct()
    {
        if ($this->connection) {
            imap_close($this->connection);
        }
    }
}