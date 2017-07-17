<?php
namespace Mail;

/**
 * Класс отправки почтовых сообщений
 *
 * Письма отправляются только в UTF-8, на все входящие параметры
 * (тема письма, содержание письма) должны быть тоже в UTF-8
 *
 * Пример использования:
 *
 *     $mail = new Sender();
 *     $mail->setSubj('Это тема письма'); // устанавливаем тему письма
 *     $mail->setHtmlBody($htmlText); // письмо отправляем в html-формате
 *     // Прикрепляем файл /var/tmp/bla-bla.jpg под именем picture.jpg
 *     $mail->fileAttach('/var/tmp/bla-bla.jpg', 'image/jpg', 'picture.jpg');
 *     // Отправляем письмо с ящика my@email.com на ящики other@email.com, second@email.com
 *     $mail->sent('my@emai.com', 'other@email.com, second@email.com');
 */
class Sender
{
    /** @var array Прикреплённые файлы */
    protected $attach = array();

    /** @var string Набор заголовков письма */
    protected $header = '';

    /** @var string Все письмо целиком */
    protected $body = null;

    /** @var string Html-формат письма */
    protected $body_html = null;

    /** @var string Текстовый формат письма */
    protected $body_plain = null;

    /** @var string Тема письма */
    protected $subj;

    /** @var bool Флаг, использовать/не использовать smtp при отправке сообщения */
    protected $isSmtp = false;

    /** @var array Массив настроек подключения к SMTP */
    protected $smtp = array();

    /**
     * Прикрепляем файл к письму, если файл существует
     *
     * @param string $path Путь к прикрепляемому файлу
     * @param string $type Тип прикрепляемого файла
     * @param string $saveAs Имя, под которым нужно прикрепить файл
     * @return boolean
     */
    public function fileAttach($path, $type, $saveAs = '')
    {
        $saveAs = ($saveAs == '') ? $path : $saveAs;
        $name = preg_replace("/(.+\/)/", '', $saveAs);
        if (!$r = fopen($path, 'r')) {
            return false;
        }
        // TODO преобразование $name из любой кодировки в UTF8
        $this->attach[$name] = array(
            'type' => $type,
            'data' => fread($r, filesize($path))
        );
        fclose($r);
        return true;
    }

    /**
     * Проверка адреса электронной почты
     *
     * @param $mail
     * @return bool
     */
    public function isEmail($mail)
    {
        $filter = filter_var($mail, FILTER_VALIDATE_EMAIL);
        return ($filter == $mail);
    }

    /**
     * Отправляет письмо получателю
     *
     * @param string $from адрес откуда будет отправлено письмо
     * @param string $to адрес кому будет отправлено письмо
     * @return bool
     */
    public function sent($from, $to)
    {
        if ($this->body === null) {
            list($this->header, $this->body) = $this->render();

            // Разрезаем строчки, длиннее 2020 символов (это ограничение почтовиков)
            $body = explode("\n", $this->body);
            foreach ($body as $k => $row) {
                // TODO придумать, как резать больше, чем один раз
                if (strlen($row) > 2030) {
                    $splitPos = strpos($row, ' ', 2020);
                    $row = substr_replace($row, "\n", $splitPos + 1, 0);
                    $body[$k] = $row;
                }
            }
            $this->body = implode("\n", $body);
        }
        // Если есть все данные для отправки по SMTP, то используем его
        if ($this->isSmtp) {
            $result = $this->mailSmtp($from, $to);
        } else {
            $result = mail($to, $this->subj, $this->body, 'From: ' . $from . "\n" . $this->header);
        }
        return $result;
    }

    /**
     * Создание основного текста письма вместе с заголовками
     *
     * @return string
     * @throws \Exception
     */
    protected function getTextPart()
    {
        // Если выбрана отправка письма только в html-виде
        if (!empty($this->body_html) && ($this->body_plain === '')) {
            $body = "Content-type: text/html; charset=utf-8\n"
                    . "Content-transfer-encoding: quoted-printable\n\n";
            $body .= quoted_printable_encode($this->body_html) . "\n\n";
            return $body;
        }

        // Если выбрана отправка письма только в текстовом виде
        if (!empty($this->body_plain) && empty($this->body_html)) {
            $body = "Content-type: text/plain; charset=utf-8\n"
                . "Content-transfer-encoding: 8bit\n\n";
            $body .= $this->body_plain . "\n\n";
            return $body;
        }

        // Если выбрана отправка письма и в html и в plain виде
        $boundary = md5(mt_rand()); // установка разделителей письма псевдослучайным образом
        $body = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . "\n\n";

        // Требуется ли сгенерировать plain-версию из html
        if (is_null($this->body_plain) && !empty($this->body_html)) {
            $this->body_plain = strip_tags($this->body_html);
        }

        // На данном этапе и plain и html-версии должны быть непустые, иначе — ошибка
        if (empty($this->body_plain) || empty($this->body_html)) {
            throw new \Exception('Для отправки письма с двумя версиями текст должен быть и в plain и html-версии ');
        }

        // Добавляем plain-версию
        $body .= '--' . $boundary . "\n";
        $body .= "Content-Type: text/plain; charset=utf-8\n";
        $body .= "Content-Transfer-Encoding: 8bit\n\n";
        $body .= quoted_printable_encode($this->body_plain) . "\n\n";

        // Добавляем html-версию
        $body .= '--' . $boundary . "\n";
        $body .= "Content-Type: text/html; charset=utf-8\n";
        $body .= "Content-Transfer-Encoding: quoted-printable\n\n";
        $body .= quoted_printable_encode($this->body_html) . "\n\n";

        // Завершение блока alternative
        $body .= '--' . $boundary . "--\n\n";

        return $body;
    }

    /**
     * Формирование заголовков и текста письма
     *
     * @return array
     * @throws \Exception
     */
    protected function render()
    {
        if (empty($this->attach)) {
            // Если аттач отсутствует, генерируем только текст
            $body = $this->getTextPart();
            // Вырезаем из текста заголовок
            list($header, $body) = explode("\n\n", $body, 2);
        } else {
            // Если добавлен аттач
            $boundary = md5(mt_rand()); // установка разделителей письма псевдослучайным образом
            $header = 'Content-Type: multipart/mixed; '
                . 'boundary="' . $boundary . '"' . "\n";
            $body = '--' . $boundary . "\n";
            $body .= $this->getTextPart(); // ставим собственно текст письма

            // Добавляем все прикреплённые файлы
            foreach ($this->attach as $name => $attach) {
                $body .= '--' . $boundary . "\n";
                $body .= "Content-Type: {$attach['type']}\n";
                $body .= "Content-Transfer-Encoding: base64\n";
                $body .= "Content-Disposition: attachment;";
                $body .= "filename*0*=UTF8''" . rawurlencode($name) . ";\n\n";
                $body .= chunk_split(base64_encode($attach['data'])) . "\n\n";
            }

            $body .= '--' . $boundary . '--';  // завершитель блоков mixed
        }

        $header = "MIME-Version: 1.0\n" . $header;

        return array($header, $body);
    }

    /**
     * Добавление текста письма в формате html
     *
     * @param $plain
     * @param $html
     * @deprecated
     */
    public function setBody($plain, $html)
    {
        $this->body_html = $html;
        $this->body_plain = $plain;
        $this->body = null;
    }

    /**
     * Добавление простого текста письма
     *
     * @param $text
     */
    public function setPlainBody($text)
    {
        $this->body_plain = $text;
        $this->body = null;
    }

    /**
     * Добавление текста письма
     *
     * @param $text
     */
    public function setHtmlBody($text)
    {
        $this->body_html = $text;
        $this->body = null;
    }

    /**
     * Установка заголовка письма
     *
     * @param $subject string Заголовок письма
     */
    public function setSubj($subject)
    {
        $subject = stripslashes($subject);
        $this->subj = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }

    /**
     * Отправляет сообщение посредством SMTP
     *
     * @param string $from Адрес отправителя
     * @param string $receiver Адреса получателей
     * @return bool Признак успеха/провала отправки сообщения
     */
    protected function mailSmtp($from, $receiver)
    {
        // Формируем массив получателей
        $receiver = explode(',', $receiver);
        foreach ($receiver as $key => $value) {
            $receiver[$key] = trim($value);
        }

        $server = $this->smtp['server'];
        $port = $this->smtp['port'];
        $user = $this->smtp['user'];
        $password = $this->smtp['password'];
        $domain = $this->smtp['domain'];

        $formatDomain = function ($str) {
            return str_replace("+", "_", str_replace("%", "=", urlencode($str)));
        };

        // Формируем заголовки
        $header = "From: =?UTF-8?Q?" . $formatDomain($domain) . "?= <{$from}>\n";
        $header .= "Reply-To: =?UTF-8?Q?" . $formatDomain($domain) . "?= <{$from}>\n";
        $header .= "Message-ID: <" . time() . "." . date("YmjHis") . "@{$domain}>\n";
        $header .= "To: =?UTF-8?Q?" . $formatDomain($receiver[0]) . "?= <{$receiver[0]}>\n";
        $header .= "Subject: {$this->subj}\n";
        $this->header = $header . $this->header;

        // Соединяемся с почтовым сервером
        $smtpConn = fsockopen($server, $port, $errno, $errstr, 30);
        if (!$smtpConn) {
            return false;
        }

        // Здороваемся с сервером
        $this->getData($smtpConn);
        fputs($smtpConn, "EHLO {$domain}\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 250) {
            fclose($smtpConn);
            return false;
        }

        // Запрашиваем авторизацию
        fputs($smtpConn, "AUTH LOGIN\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 334) {
            fclose($smtpConn);
            return false;
        }

        // Отправляем логин
        fputs($smtpConn, base64_encode($user) . "\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 334) {
            fclose($smtpConn);
            return false;
        }

        // Отправляем пароль
        fputs($smtpConn, base64_encode($password) . "\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 235) {
            fclose($smtpConn);
            return false;
        }

        // Посылаем информацию об отправителе и размере писма
        $sizeMsg = strlen($this->header ."\n". $this->body);
        fputs($smtpConn, "MAIL FROM:<{$user}> SIZE=" . $sizeMsg . "\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 250) {
            fclose($smtpConn);
            return false;
        }

        // Посылаем информацию о получателях
        foreach ($receiver as $value) {
            fputs($smtpConn, "RCPT TO:<{$value}>\n");
            $code = substr($this->getData($smtpConn), 0, 3);
            if ($code != 250 && $code != 251) {
                fclose($smtpConn);
                return false;
            }
        }

        // Запрашиваем возможность ввода текста письма
        fputs($smtpConn, "DATA\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 354) {
            fclose($smtpConn);
            return false;
        }

        // Отправляем текст письма со всеми заголовками
        fputs($smtpConn, $this->header ."\n". $this->body . "\n.\n");
        $code = substr($this->getData($smtpConn), 0, 3);
        if ($code != 250) {
            fclose($smtpConn);
            return false;
        }

        // Завершаем работу с сервером
        fputs($smtpConn, "QUIT\n");
        fclose($smtpConn);
        return true;
    }

    /**
     * Получает ответ от сервера
     *
     * @param $smtpConn resource Указатель на открытое соединение с сервером
     * @return string Ответ от сервера
     */
    protected function getData($smtpConn)
    {
        $data = "";
        while ($str = fgets($smtpConn, 515)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $data;
    }

    /**
     * Определяет достаточность предоставленных параметров для использования SMTP
     */
    public function setSmtp($params)
    {
        // Проверяем корректность переданных параметров подключения к SMTP
        $fields = array('server', 'port', 'user', 'password', 'domain');
        foreach ($fields as $field) {
            if (empty($params[$field])) {
                throw new \Exception('Отсутствует поле ' . $field . ' в настройках SMTP');
            }
        }
        $this->smtp = $params;
        $this->isSmtp = true;
    }
}
