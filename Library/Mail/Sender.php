<?php
/**
 * Класс отправки почтовых сообщений
 *
 * Письма отправляются только в UTF-8, на все входящие параметры
 * (тема письма, содержание письма) должны быть тоже в UTF-8
 *
 */

namespace Mail;

class Sender
{

    protected $attach; // вложенные файлы

    protected $attach_type; // типы (форматы) вложенных файлов

    /** @var string Набор заголовков письма */
    protected $header = '';

    protected $body; // все письмо целиком

    protected $body_html = null; // html-формат письма

    protected $body_plain = null; // plain-text -- обычный текст письма

    protected $boundary;

    protected $boundary2;

    protected $charset; // Кодировка письма

    protected $from;  // отправитель

    protected $header_txt;

    protected $priority = 3; // приоритет письма

    protected $subj; // тема письма

    protected $to; // получатель

    public function __construct()
    {
        // Установка разделителей письма псевдослучайным образом
        $this->boundary = md5(mt_rand());
        $this->boundary2 = md5(mt_rand());
        // Установка кодировки по умолчанию
        $this->charset = 'utf-8';

        $this->body = null;
    }

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
        $this->attach($name, $type, fread($r, filesize($path)));
        fclose($r);
        return true;
    }

    /**
     * Добавление вложения в письмо
     *
     * @param $name
     * @param $type
     * @param $data
     */
    protected function attach($name, $type, $data)
    {
        $this->attach_type[$name] = $type;
        $this->attach[$name] = $data;
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
        if ($filter == $mail) {
            return true;
        }
        return false;
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
        $this->from = $this->convIn($from);
        $this->to = $this->convIn($to);

        if ($this->body === null) {
            list($this->header, $this->body) = $this->render();
            $this->body = $this->convIn($this->body);

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
        return mail($this->to, $this->subj, $this->body, 'From: ' . $this->from . "\n" . $this->header);
    }

    /**
     * Устанавливает кодировку текста
     *
     * @param $text
     * @return string
     */
    protected function convIn($text)
    {
        $code = mb_detect_encoding($text);
        if (strnatcasecmp($this->charset, $code) === 0) {
            return $text;
        }
        $text = mb_convert_encoding($text, $this->charset, $code);
        return $text;
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
                . "Content-transfer-encoding: 8bit\n\n";
            $body .= $this->body_html . "\n\n";
            return $body;
        }

        // Если выбрана отправка письма только в текстовом виде
        if (!empty($this->body_plain) && ($this->body_html === '')) {
            $body = "Content-type: text/plain; charset=utf-8\n"
                . "Content-transfer-encoding: 8bit\n\n";
            $body .= $this->body_plain . "\n\n";
            return $body;
        }

        // Если выбрана отправка письма и в html и в plain виде
        $body = 'Content-Type: multipart/alternative; boundary="' . $this->boundary . '"' . "\n\n";

        // Требуется ли сгенерировать plain-версию из html
        if (is_null($this->body_plain) && !empty($this->body_html)) {
            $this->body_plain = strip_tags($this->body_html);
        }

        // На данном этапе и plain и html-версии должны быть непустые, иначе — ошибка
        if (empty($this->body_plain) || empty($this->body_html)) {
            throw new \Exception('Для отправки письма с двумя версиями текст должен быть и в plain и html-версии ');
        }

        // Добавляем plain-версию
        $body .= '--' . $this->boundary . "\n";
        $body .= "Content-Type: text/plain; charset=utf-8\n";
        $body .= "Content-Transfer-Encoding: 8bit\n\n";
        $body .= $this->body_plain . "\n\n";

        // Добавляем html-версию
        $body .= '--' . $this->boundary . "\n";
        $body .= "Content-Type: text/html; charset=utf-8\n";
        $body .= "Content-Transfer-Encoding: 8bit\n\n";
        $body .= $this->body_html . "\n\n";

        // Завершение блока alternative
        $body .= '--' . $this->boundary . "--\n\n";

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
        if (!isset($this->attach_type)) {
            // Если аттач отсутствует, генерируем только текст
            $body = $this->getTextPart();
            // Вырезаем из текста заголовок
            list($header, $body) = explode("\n\n", $body, 2);
        } else {
            // Если добавлен аттач
            $header = 'Content-Type: multipart/mixed; '
                . 'boundary="' . $this->boundary2 . '"' . "\n";
            $body = '--' . $this->boundary2 . "\n";
            $body .= $this->getTextPart();
            reset($this->attach_type);
            // Добавляем все прикреплённые файлы
            while (list($name, $content_type) = each($this->attach_type)) {
                // $this->body .= '--' . $this->boundary . "--\n"; -- вот это нафига тут?
                $body .= '--' . $this->boundary2 . "\n";
                $body .= "Content-Type: {$content_type}\n";
                $body .= "Content-Transfer-Encoding: base64\n";
                $body .= "Content-Disposition: attachment;";
                // TODO тут надо сделать преобразование $name из любой кодировки в UTF8
                $body .= "filename*0*=UTF8''" . rawurlencode($name) . ";\n\n";
                $body .= chunk_split(base64_encode($this->attach[$name])) . "\n\n";
            }
            $body .= '--' . $this->boundary2 . '--';
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
     * Установка кодировки письма
     *
     * @param $code
     */
    public function setCharset($code)
    {
        $this->charset = strtoupper($code);
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
}
