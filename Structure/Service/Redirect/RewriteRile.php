<?php

namespace Ideal\Structure\Service\Redirect;

class RewriteRile
{
    protected $fileName; // файл с редиректами
    protected $params; // правила для редиректов где ключ 1 - откуда, 2 - куда перенаправлять
    protected $htaccess = true; // Сохранять в htaccess или нет

    public function loadFile($fileName)
    {
        $this->params = array();
        $this->fileName = $fileName;
        if (!file_exists($this->fileName)) {
            $fp = fopen($this->fileName, "w"); // создаем файл
            fclose($fp);
        }
        $file_handle = fopen($this->fileName, "r");
        while (!feof($file_handle)) {
            $line = trim(fgets($file_handle));
            $line = preg_replace("/\s{2,}/", " ", $line);
            if (strlen($line) < 10)
                continue;
            $line = explode(' ', $line, 4);
            $this->params[] = $line;
        }
        fclose($file_handle);
    }

    public function getCountParam()
    {
        return count($this->params);
    }

    /**
     * Создает новый редирект
     * Возвращает текст, для парсера js, ввиде json. Где
     * error - была ли ошибка
     * line - номер строки из-за которой появилась ошибка
     * text - подробное описание
     */
    public function addLine()
    {
        $answer = array('error' => false);
        // Изменяем постоянные настройки сайта
        $tmp = array();
        $tmp[0] = 'RewriteRule';
        $tmp[1] = $_POST['from'];
        $tmp[2] = $_POST['on'];
        $tmp[3] = '[R=301,L]';
        foreach ($this->params as $k => $v) {
            $k++; // В таблице нумерация строк начинается с 1, а не с 0

            // Проверяем на повторение редиректа, цикличностии и отсутсвие цепочек редиректов
            if (strnatcasecmp($v[1], $tmp[1]) == 0) {
                if (strnatcasecmp($v[2], $tmp[2]) == 0) {
                    $answer['text'] = "Такой уже редирект существует в строке {$k}";
                } else {
                    $answer['text'] = "Редирект с {$tmp[1]} уже существует и переадресует на {$tmp[2]}";
                }
                $answer['error'] = true;
            }
            if (strnatcasecmp($tmp[2], $v[1]) == 0) {
                if (strnatcasecmp($tmp[1], $v[2]) == 0) {
                    $answer['text'] = "Организуется бесконечный редирект с {$tmp[1]} на {$tmp[2]}, помогает этому строка {$k}";
                } else {
                    $answer['text'] = "Организуется множественный редирект с {$tmp[1]} на {$tmp[2]}, а потом на {$v[2]}";
                }
                $answer['error'] = true;
            }
            if (strnatcasecmp($tmp[1], $v[2]) == 0) {
                if (strnatcasecmp($tmp[2], $v[1]) == 0) {
                    $answer['text'] = "Организуется бесконечный редирект с {$tmp[1]} на {$tmp[2]}, помогает этому строка {$k}";
                } else {
                    $answer['text'] = "Организуется множественный редирект с {$v[1]} на {$v[2]}, а потом на {$tmp[2]}";
                }
                $answer['error'] = true;
            }
            if ($answer['error']) {
                print json_encode($answer);
                exit;
            }
        }
        $this->params[] = $tmp;
        unset($tmp);
        print json_encode($answer);
    }

    /**
     * Удаляет один из редиректов
     */
    public function deleteLine()
    {
        $tmp[1] = $_POST['from'];
        $tmp[2] = $_POST['on'];
        foreach ($this->params as $k => $v) {
            // Ищем нужный нам редирект
            if (strnatcasecmp($v[1], $tmp[1]) == 0) {
                if (strnatcasecmp($v[2], $tmp[2]) == 0) {
                    unset($this->params[$k]);
                }
            }
        }
    }

    /**
     * Сохраняет изменения в редиректах в файл
     * Во время записипроверяет чтобы не было циклов редиректов, пустых редиректов
     */
    public function saveFile()
    {
        $fp = fopen($this->fileName, 'w');
        $file = '';
        foreach ($this->params as $k => $v) {
            if (!isset($v[1]) || $v[1] == '')
                continue;
            if (!isset($v[2]) || $v[2] == '')
                continue;
            if (strnatcasecmp($v[1], $v[2]) === 0)
                continue;
            $file .= "{$v[0]} {$v[1]} {$v[2]} {$v[3]}\n";
        }
        fwrite($fp, $file);
        // Записсь в htaccess
        if($this->htaccess){
            $t = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/.htaccess');
            $file = preg_replace('/(#START redirect)(\r\n|\r|\n)([^}]*)(#END redirect)/', "$1$2".$file."$2$4", $t);
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/.htaccess', $file);
        }
    }

    /**
     * TODO реализиция редиректов посредствам PHP
     */
    public function checkUrl($url = null)
    {
        $url = ($url == null) ? $_GET['url'] : $url;
        foreach ($this->params as $v) {
            if (strnatcasecmp($v[1], $url) === 0) {
                // TODO перенаправить по редиректу
            }
        }
    }

    /**
     * Выводит уже существующие редиректы в виде строки таблицы
     * @return string
     */
    public function showEdit()
    {
        $str = '';
        $i = 1;
        foreach ($this->params as $k => $v) {
            $str .= <<<RULE
            <tr id="line{$i}">
<td class="from">{$v[1]}</td><td class="on">{$v[2]}</td>
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
        $str .= '';
        return $str;
    }

}
