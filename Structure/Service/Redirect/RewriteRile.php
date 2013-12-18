<?php

namespace Ideal\Structure\Service\Redirect;

use Ideal\Core\Config;

class RewriteRile
{
    protected $fileName; // файл с редиректами
    protected $params; // правила для редиректов где ключ 1 - откуда, 2 - куда перенаправлять
    protected $htaccess = true; // Сохранять в htaccess или нет
    protected $msg = ''; // Сообщение для вывода ошибок и предупреждений
    protected $error = 0; // 0 - отсутвие ошибок, 1 - присутсвуют не критичные ошибки, 2 - критические ошибки, прерывание работы скрипта

    /**
     * Происходит загрузка редиректов из .htaccess и redirect.txt
     * @return bool Успешность выполнения
     */
    public function newLoadFile()
    {
        // Сегмент обработки файла redirect.txt
        $config = Config::getInstance();
        $redirect = $config->cmsFolder . '/redirect.txt'; // Путь к redirect.txt
        if (!file_exists($redirect)) {
            $fp = fopen($this->fileName, "w"); // создаем файл
            fclose($fp);
            $this->msg .= "<div class='alert alert-info'>Создан файл redirect.txt</div>";
        }
        if (!is_writable($redirect)) {
            $this->msg .= "<div class='alert alert-block'>Файл redirect.txt не доступен на запись</div>";
            $this->error = 1;
        }
        $redirect = file_get_contents($redirect); // Загружаем файл redirect.txt в память
        $redirectFromRe = array();
        preg_match_all('/RewriteRule(.*)\[/U', $redirect, $redirectFromRe);
        if (isset($redirectFromRe[1]) && count($redirectFromRe[1]) > 0) {
            foreach ($redirectFromRe[1] as $key => $val) {
                // Убираем пробелы по краям
                $val = trim($val);
                // Между откуда и куда присутсвует единсвенный пробел, больше их быть не может, смело по нему разбиваем
                $val = explode(' ', $val, 2);
                $this->params[$val[0]] = $val[1];
            }
        }
        // Сегмент обработки файла .htaccess
        $htaccess = DOCUMENT_ROOT . '/.htaccess'; // Путь к .htaccess
        if (!file_exists($htaccess) && !is_writable($htaccess)) {
            $this->msg .= "Файл не существует или не доступен на запись\n";
            $this->error = 2;
            return false;
        }
        $htaccess = file_get_contents($htaccess); // Загружаем файл htaccess в память
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
                // Между "откуда" и "куда" присутсвует единсвенный пробел, больше их быть не может, смело по нему разбиваем
                $val = explode(' ', $val, 2);
                if(isset($this->params[$val[0]]) && $this->params[$val[0]] != $val[1]){
                    $tmp = $this->params[$val[0]];
                    unset($this->params[$val[0]]);
                    $this->params[$val[0]]['to1'] = $tmp;
                    $this->params[$val[0]]['to2'] = $val[1];
                    $this->params[$val[0]]['error'] = 1;
                    $this->msg .= '<div class="alert alert-error">Ошибка в редиректах. Устранить нужно все и сразу иначе затрутся</div>';
                }else{
                    if(!isset($this->params[$val[0]])){
                        $this->params[$val[0]]['to'] = $val[1];
                        $this->params[$val[0]]['error'] = 2;
                    }
                }
            }
        }

        return true;
    }

    public function getMsg()
    {
        return $this->msg;
    }

    public function getError()
    {
        return $this->error;
    }


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
        if ($this->htaccess) {
            $t = file_get_contents(DOCUMENT_ROOT . '/.htaccess');
            $file = preg_replace('/\#START redirect(.*)\#END redirect/s', "$1$2" . $file . "$2$4", $t);
            file_put_contents(DOCUMENT_ROOT . '/.htaccess', $file);
        }
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
            $defaultFrom = $k;
            $defaultTo = $v;
            if(is_array($v)){
                if ($v['error'] == 1) {
                    $class = "class='error'";
                    $defaultTo = $v['to1'];
                    $v = 'REDIRECT.txt: '. $v['to1'].' HTACCESS: '.$v['to2'];
                    $this->msg .= "<div class='alert alert-error'>".
                        "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                        "Разница в редиректах. Необходимо исправить. <a href='#line{$i}'>Подробнее</a></div>";
                } elseif ($v['error'] == 2) {
                    $defaultTo = $v['to'];
                    $class = "class='warning'";
                    $this->msg .= "<div class='alert alert-block'>".
                        "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                        "Удаленный редирект. Необходимо исправить. "."
                        <a href='#' onclick=\"scrollToElement('line{$i}'); return false;\">Подробнее</a></div>";
                    $v = $v['to'];
                }
            }
            $str .= <<<RULE
            <tr id="line{$i}" {$class}>
<td class="from" data-from="{$defaultFrom}">{$k}</td><td class="on" data-to="{$defaultTo}">{$v}</td>
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
