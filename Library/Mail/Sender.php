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
        $this->boundary = '----_=_' . md5(mt_rand());
        $this->boundary2 = '----_=_' . md5(mt_rand());
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
        $header = $this->convIn($this->header());

        if ($this->body === null) {
            $this->bodyCreate();
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

        return mail($this->to, $this->subj, $this->body, 'From: ' . $this->from . "\r\n" . $header);
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
        if (function_exists('mb_convert_encoding')) {
            $text = mb_convert_encoding($text, $this->charset, $code);
        } else {
            if (function_exists('iconv')) {
                $text = iconv(iconv, $this->charset, $text);
            }
        }
        return $text;
    }

    /**
     * Создание заголовка письма
     *
     * @return string
     */
    protected function header()
    {
        $header = "MIME-Version: 1.0\n";

        $header_cnt = '';
        if ($this->body_plain != "") {
            // Если выбрана отправка письма только в текстовом виде,
            $header_cnt = "Content-type: text/plain; charset=utf-8\n"
                . "Content-transfer-encoding: 8bit";
        } elseif ($this->body_html != "") {
            // Если выбрана отправка письма только в текстовом виде,
            $header_cnt = "Content-type: text/html; charset=utf-8\n"
                . "Content-transfer-encoding: 8bit";
        }

        // Если добавлен аттач
        if (isset($this->attach_type)) {
            $header_cnt = 'Content-Type: multipart/mixed; '
                . 'boundary="' . $this->boundary2 . '"';
        }

        $header .= $header_cnt;

        return $header;
    }

    /**
     * Подготавливаем и добавляем версию письма без разметки
     *
     *
     */
    protected function bodyCreate()
    {
        if (isset($this->attach_type)) {
            // Если есть аттач
            $this->body .= '--' . $this->boundary2 . "\n";
            if (($this->body_html != '') || ($this->body_html != null)) {
                $this->body .= 'Content-Type: multipart/alternative; boundary="' . $this->boundary . '"' . "\n\n";
            } else {
                $this->body .= "Content-Type: text/plain; charset=utf-8\n";
                $this->body .= "Content-Transfer-Encoding: 8bit\n\n";
            }
        }

        if ($this->body_plain !== '' && $this->body_html != '') {
            // Если HTML
            $this->body .= '--' . $this->boundary . "\n";
            $this->body .= "Content-Type: text/plain; charset=utf-8\n";
            $this->body .= "Content-Transfer-Encoding: 8bit\n\n";
        }

        if ($this->body_html != '' && $this->body_plain === null) {
            // Если в html-режиме не задан обычный вид письма - сгенерировать его из html
            $this->body_plain = strip_tags($this->body_html);
        }

        if ($this->body_plain !== '') {
            $this->body .= $this->body_plain . "\n\n";
        }

        // Если html-версия текста письма есть!
        if (strlen($this->body_html) > 0 && (($this->body_plain !== '') || ($this->attach_type))) {
            $this->body .= '--' . $this->boundary . "\n";
            $this->body .= "Content-Type: text/html; charset=utf-8\n";
            $this->body .= "Content-Transfer-Encoding: quoted-printable\n\n";
            $this->body .= $this->body_html . "\n\n";
        } elseif (strlen($this->body_html) > 0) {
            $this->body .= $this->body_html . "\n\n";
        }

        if (isset($this->attach_type)) {
            reset($this->attach_type);
            while (list($name, $content_type) = each($this->attach_type)) {
                $this->body .= '--' . $this->boundary . "--\n";
                $this->body .= '--' . $this->boundary2 . "\n";
                $this->body .= "Content-Type: {$content_type}\n";
                $this->body .= "Content-Transfer-Encoding: base64\n";
                $this->body .= "Content-Disposition: attachment;";
                // TODO тут надо сделать преобразование $name из любой кодировки в UTF8
                $this->body .= "filename*0*=UTF8''" . rawurlencode($name) . ";\n\n";
                $this->body .= chunk_split(base64_encode($this->attach[$name])) . "\n\n";
            }
            $this->body .= '--' . $this->boundary2 . '--';
        }
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
