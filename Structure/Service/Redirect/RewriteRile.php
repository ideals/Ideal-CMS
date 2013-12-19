<?php

namespace Ideal\Structure\Service\Redirect;

use Ideal\Core\Config;

class RewriteRile
{
    /** @var array Ключ откуда редирект, значение куда. Если присутсвует массив error, то значение
     * 1 указывает что значения двух файлов различаються
     * 2 указывает на отсутствие в одном из фалойв
     */
    protected $params;
    protected $msg = ''; // Сообщение для вывода ошибок и предупреждений
    protected $error = 0; // 0 - отсутвие ошибок, 1 - присутсвуют не критичные ошибки, 2 - критические ошибки, прерывание работы скрипта
    protected $htFile; // Файл .htaccess
    protected $reFile; // Файл redirect.txt

    public function __construct()
    {
        $config = Config::getInstance();
        $this->htFile = DOCUMENT_ROOT . '/.htaccess';
        $this->reFile = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/redirect.txt';
    }

    /**
     * Происходит загрузка редиректов из .htaccess и redirect.txt
     * @return bool Успешность выполнения
     */
    public function LoadFile()
    {
        // Сегмент обработки файла redirect.txt
        if (!file_exists($this->reFile)) {
            $fp = fopen($this->reFile, "w"); // создаем файл
            fclose($fp);
            $this->msg .= "<div class='alert alert-info'>Создан файл redirect.txt</div>";
        }
        if (!is_writable($this->reFile)) {
            $this->msg .= "<div class='alert alert-block'>Файл redirect.txt не доступен на запись</div>";
            $this->error = 1;
        }
        $redirect = file_get_contents($this->reFile); // Загружаем файл redirect.txt в память
        $redirectFromRe = array();
        preg_match_all('/RewriteRule(.*)\[/U', $redirect, $redirectFromRe);
        if (isset($redirectFromRe[1]) && count($redirectFromRe[1]) > 0) {
            foreach ($redirectFromRe[1] as $key => $val) {
                // Убираем пробелы по краям
                $val = trim($val);
                // Переводим все буквы в нижний регистр
                $val = mb_strtolower($val);
                // Между откуда и куда присутсвует единсвенный пробел, больше их быть не может, смело по нему разбиваем
                $val = explode(' ', $val, 2);
                $this->params[$val[0]] = $val[1];
            }
        }
        // Сегмент обработки файла .htaccess
        if (!file_exists($this->htFile) && !is_writable($this->htFile)) {
            $this->msg .= "Файл не существует или не доступен на запись\n";
            $this->error = 2;
            return false;
        }
        $htaccess = file_get_contents($this->htFile); // Загружаем файл htaccess в память
        $check = array();
        preg_match_all('/\#redirect/', $htaccess, $check); // Ищем в htaccess теги #redirect возвращаем содержимое тегов
        if (!isset($check[0]) || count($check[0]) < 2) {
            // В случае не нахождения или отсутвие второго тега, прекращаем обработку и выходим записав ошибку
            $this->error = 2;
            $this->msg .= "<div class='alert alert-error'>В .htaccess отсутсвует теги #redirect</div>";
            return false;
        } else {
            $redirectFromHt = array();
            preg_match_all('/\#redirect(.*)\#redirect/s', $htaccess, $redirectFromHt);
            preg_match_all('/RewriteRule(.*)\[/U', $redirectFromHt[1][0], $redirectFromHt);
            if (count($check[0]) > 2) {
                $this->msg .= "<div class='alert alert-block'>В htaccess перебор с тегом #redirect</div>";
                $this->error = 1;
            }
            foreach ($redirectFromHt[1] as $key => $val) {
                if (strlen($val) < 4 || strpos($val, 'RewriteRule') !== false || strpos($val, '#redirect') !== false) {
                    continue;
                }
                // Убераем из строки лишнии слова, оставляем только правило откуда куда редирект
                $val = preg_replace('/RewriteRule(.*)\[(.*)/s', '$1', $val);
                // Убираем пробелы по краям
                $val = trim($val);
                // Переводим все буквы в нижний регистр
                $val = mb_strtolower($val);
                // Между "откуда" и "куда" присутсвует единсвенный пробел, больше их быть не может, смело по нему разбиваем
                $val = explode(' ', $val, 2);
                if (isset($this->params[$val[0]]) && $this->params[$val[0]] != $val[1]) {
                    $tmp = $this->params[$val[0]];
                    unset($this->params[$val[0]]);
                    $this->params[$val[0]]['on1'] = $tmp;
                    $this->params[$val[0]]['on2'] = $val[1];
                    $this->params[$val[0]]['error'] = 1;
                    $this->msg .= '<div class="alert alert-error">Ошибка в редиректах. Устранить нужно все и сразу иначе затрутся</div>';
                } else {
                    if (!isset($this->params[$val[0]])) {
                        $this->params[$val[0]]['on'] = $val[1];
                        $this->params[$val[0]]['error'] = 2;
                    }
                }
            }
        }

        return true;
    }

    /**
     * @return string Сообщение сформированное за время выполнение скрипта
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * @return int Уровень ошибки
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return int Кол-во редиректов
     */
    public function getCountParam()
    {
        return count($this->params);
    }

    /**
     * Создание нового редиректа
     * @param bool $newFrom требуется если создаем редирект на основе старого
     * @param bool $newOn требуется если создаем редирект на основе старого
     * @return array требуется если создаем редирект на основе старого
     */
    public function addLine($newFrom = false, $newOn = false)
    {
        $answer = array('error' => false, 'text' => '');
        // Изменяем постоянные настройки сайта
        if (!$newFrom && !$newOn) {
            $from = mb_strtolower($_POST['from']);
            $on = mb_strtolower($_POST['on']);
        } else {
            $from = $newFrom;
            $on = $newOn;
        }
        if (isset($this->params[$from]['error'])) {
            unset($this->params[$from]);
        }
        if (isset($this->params[$from])) {
            if ($this->params[$from] == $on) {
                $answer['text'] = "Такой уже редирект существует";
            } else {
                $answer['text'] = "Редирект с {$from} уже существует и переадресует на {$this->params[$from]}";
            }
            $answer['error'] = true;
        } elseif (isset($this->params[$on])) {
            if ($this->params[$on] == $from) {
                $answer['text'] = "Организуется бесконечный редирект с {$from} на {$on} потом {$from}. Бесконечный цикл";
            } else {
                $answer['text'] = "Организуется множественный редирект с {$from} на {$on}, а потом на {$this->params[$on]}";
            }
            $answer['error'] = true;
        } else {
            $this->params[$from] = $on;
        }
        if ($newFrom) {
            return $answer;
        }
        if (!$answer['error']) {
            $this->saveFile();
        }
        print json_encode($answer);
        exit;

    }

    /**
     * Редактирование редиректа
     */
    public function editLine()
    {
        $answer = array('error' => false, 'text' => '');
        $from = mb_strtolower($_POST['from']);
        $on = mb_strtolower($_POST['on']);
        $oldFrom = mb_strtolower($_POST['oldFrom']);
        $oldOn = mb_strtolower($_POST['oldOn']);
        if ($from == $oldFrom) {
            if ($on != $oldOn) {
                if (isset($this->params[$on])) {
                    $answer['text'] = "Организуется множественный редирект с {$from} на {$on}, а потом на {$this->params[$on]}";
                    $answer['error'] = true;
                } elseif ($on == $from || ($this->params[$on] == $from)) {
                    $answer['text'] = "Организуется бесконечный редирект с {$from} на {$on} потом {$from}. Бесконечный цикл";
                    $answer['error'] = true;
                } else {
                    $this->params[$from] = $on;
                }
            }
        } else {
            if (isset($this->params[$oldFrom])) unset($this->params[$oldFrom]);
            $answer = $this->addLine($from, $on);
        }
        if (!$answer['error']) {
            $this->saveFile();
        }
        print json_encode($answer);
        exit;
    }

    /**
     * Удаляет один из редиректов
     */
    public function deleteLine()
    {
        $answer = array('error' => false, 'text' => '');
        $from = $_POST['from'];
        $on = $_POST['on'];
        if (isset($this->params[$from])) {
            if ($this->params[$from] == $on) {
                unset($this->params[$from]);
            } else {
                $answer['error'] = true;
                $answer['text'] = "Редирект с {$from} на {$this->param[$from]}.";
            }
        }
        if (!$answer['error']) {
            $this->saveFile();
        }
        print json_encode($answer);
        exit;
    }

    /**
     * Сохраняет изменения в редиректах в файл
     * Во время записипроверяет чтобы не было циклов редиректов, пустых редиректов
     */
    private function saveFile()
    {
        $file = '';
        ksort($this->params);
        foreach ($this->params as $k => $v) {
            if (is_array($v)) {
                continue;
            }
            $file .= "RewriteRule {$k} {$v} [R=301,L]\n";
        }
        file_put_contents($this->reFile, $file);
        // Записсь в htaccess
        $t = file_get_contents(DOCUMENT_ROOT . '/.htaccess');
        $file = preg_replace('/\#redirect(.*)\#redirect/s', "#redirect\n" . $file . "#redirect", $t);
        file_put_contents($this->htFile, $file);
        return true;
    }

    /**
     * Выводит уже существующие редиректы в виде строки таблицы
     * @return string
     */
    public function getTable()
    {
        $str = '';
        $i = 1;
        foreach ($this->params as $k => $v) {
            $class = '';
            $defaultFrom = "data-from='$k''";
            $defaultOn = "data-on='$v''";
            if (is_array($v)) {
                if ($v['error'] == 1) {
                    $class = "class='error'";
                    $defaultOn = '';
                    $v = 'REDIRECT.txt: ' . $v['on1'] . ' HTACCESS: ' . $v['on2'];
                    $this->msg .= "<div class='alert alert-error'>" .
                        "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                        "Разница в редиректах. Необходимо исправить. <a href='#line{$i}'>Подробнее</a></div>";
                } elseif ($v['error'] == 2) {
                    $defaultOn = '';
                    $defaultFrom = '';
                    $class = "class='warning'";
                    $this->msg .= "<div class='alert alert-block'>" .
                        "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                        "Удаленный редирект. Необходимо исправить. " . "
                        <a href='#' onclick=\"scrollToElement('line{$i}'); return false;\">Подробнее</a></div>";
                    $v = $v['on'];
                }
            }
            $str .= <<<RULE
            <tr id="line{$i}" {$class}>
<td class="from" {$defaultFrom}>{$k}</td><td class="on" {$defaultOn}>{$v}</td>
<td><div class="hide editGroup">
    <span class="input-prepend">
    <button style="width: 47px;" onclick="editLine({$i})" title="Изменить" class="btn btn-info btn-mini">
    <i class="icon-pencil icon-white"></i></button></span>
    <span class="input-append"><button onclick="delLine({$i})" title="Удалить" class="btn btn-danger btn-mini">
    <i class="icon-remove icon-white"></i></button></span></div>
</td>
</tr>
RULE;
            $i++;
        }
        return $str;
    }

}
