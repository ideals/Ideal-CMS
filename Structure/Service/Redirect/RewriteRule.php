<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2013 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Redirect;

use Ideal\Core\Config;

/**
 * Class RewriteRule
 * Синхронизация и обработка (редактирование/удаление/добавление) редиректов в файлах
 * redirect.txt и .htaccess
 */
class RewriteRule
{
    /**
     * @var array Массив редиректов: ключ — откуда редирект, значение — куда.
     * Элементы списка редиректов являются массивами и содержат:
     * to — куда ведёт редирект
     * error — сообщение об ошибке редиректа
     * htaccess — аналогичный массив редиректа из файла .htaccess, если он идёт с того же ключа
     */
    protected $redirects;

    /** @var string Сообщение для вывода ошибок и предупреждений */
    protected $msg = '';

    /**
     * @var int Тип ошибки обработки файлов редиректов
     * 0 - отсутствие ошибок,
     * 1 - присутствуют ошибки исправимые в админке,
     * 2 - присутствуют ошибки, которые нужно исправлять на уровне файловой системы (редиректы не отображаются) */
    protected $error = 0;

    /** @var string Путь к файлу .htaccess */
    protected $htFile;

    /** @var  string Путь к файлу redirect.txt */
    protected $reFile;

    /**
     * Инициализация путей к файлам редиректов и создание файла redirect.txt, если он не существует
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->htFile = DOCUMENT_ROOT . '/.htaccess';
        $this->reFile = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/redirect.txt';

        // Создаём файл redirects.txt, если он ещё не создан
        if (!file_exists($this->reFile)) {
            $fp = fopen($this->reFile, "w"); // создаем файл
            fwrite($fp, "#redirect#\n#redirect#"); // записываем в него теги редиректа
            fclose($fp);
            $this->msg .= "<div class='alert alert-info'>Создан файл redirect.txt</div>";
        }
    }

    /**
     * Загрузка редиректов из .htaccess и redirect.txt
     *
     * @return bool Успешность выполнения
     */
    public function loadRedirects()
    {
        // Загружаем редиректы из redirect.txt
        $redirectTxt = $this->loadFile($this->reFile);

        // Загружаем редиректы из .htaccess
        $redirectHtaccess = $this->loadFile($this->htFile);

        // Основным считается список редиректов из redirect.txt
        $this->redirects = $redirectTxt;

        // Уведомление о том, когда есть в redirect.txt и нет в htaccess
        foreach ($redirectTxt as $from => $v) {
            if (!isset($redirectHtaccess[$from])) {
                $v['error'] .= 'Редирект есть в redirect.txt и нет в .htaccess<br />';
                $this->redirects[$from] = $v;
            }
        }

        // Проходимся по редиректам из .htaccess и добавляем недостающие в основной список
        foreach ($redirectHtaccess as $from => $v) {
            if (!isset($this->redirects[$from])) {
                $v['error'] .= 'Редирект есть в .htaccess и нет в redirect.txt<br />';
                $this->redirects[$from] = $v;
                continue;
            }
            if ($this->redirects[$from] != $v) {
                // Если в htaccess не такой редирект, то добавляем информацию о нём
                $this->redirects[$from]['htaccess'] = $v;
                $this->msg .= '<div class="alert alert-error">Редиректы в redirect.txt и .htaccess отличаются. Необходимо срочно решить конфликты, иначе данные могут затереться.</div>';
            }
        }
    }

    /**
     * Загрузка редиректов из указанного файла $file
     *
     * @param $file string Полный путь к файлу с редиректами
     * @return array|bool  Массив с редиректами, либо false — если не удалось считать редиректы
     */
    protected function loadFile($file)
    {
        $fileName = basename($file);

        // Проверяем, доступен ли файл для записи
        if (!is_writable($file)) {
            $this->msg .= "<div class='alert alert-block'>Файл {$file} недоступен для записи</div>";
            $this->error = 2;
            return false;
        }

        $fileContent = file_get_contents($file); // Загружаем файл с редиректами в память

        $check = array();
        $countTags = preg_match_all('/\#redirect\#/', $fileContent, $check); // Ищем теги #redirect# и возвращаем их содержимое
        if ($countTags == 0) {
            // Нет ни одного тега #redirect#, прекращаем обработку и выходим записав ошибку
            $this->error = 2;
            $this->msg .= "<div class='alert alert-error'>В файле {$file} отсутствуют теги #redirect#</div>";
            return false;
        }
        if ($countTags < 2) {
            // Только один тег #redirect#, прекращаем обработку и выходим записав ошибку
            $this->error = 2;
            $this->msg .= "<div class='alert alert-error'>В файле {$file} отсутствует закрывающий тег #redirect#</div>";
            return false;
        }
        if ($countTags > 2) {
            // Если больше двух тегов #redirect, прекращаем обработку и выходим записав ошибку
            $this->error = 2;
            $this->msg .= "<div class='alert alert-block'>В файле {$file} больше двух тегов #redirect#, а должно быть только два</div>";
            return false;
        }

        // Выцепляем строчки наших редиректов между тегами #redirect в переменную $redirects
        $redirects = $params = array();
        preg_match_all('/\#redirect\#(.*)\#redirect\#/s', $fileContent, $redirects);
        preg_match_all('/RewriteRule(.*)\[/U', $redirects[1][0], $redirects);
        foreach ($redirects[1] as $val) {
            // Убираем пробелы по краям
            $val = trim($val);
            // Пропускаем пустые строки
            if ($val == '') continue;
            // Между "откуда" и "куда" присутствует единственный пробел, больше их быть не может, смело по нему разбиваем
            list($from, $to) = explode(' ', $val, 2);

            // Проверяем, нет ли каких ошибок при парсинге редиректов
            $param = array('error' => '');
            if ($to === null) {
                $to = '';
                $param['error'] .= "{$fileName}: Неправильное правило: {$val}<br />";
            }
            if (isset($params[$from])) {
                // Поскольку в htaccess срабатывает первый по порядку редирект, поэтому в списке только он и останется
                $params[$from]['error'] .= "{$fileName}: присутствует лишний редирект на {$to}<br />";
                continue;
            }
            $param['to'] = $to;
            $params[$from] = $param;
        }

        return $params;
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
        return count($this->redirects);
    }

    /**
     * Создание нового редиректа (либо замена старого)
     *
     * @param string $from Откуда
     * @param string $to Куда
     * @param bool|string $oldFrom Заменяемый редирект
     */
    public function addLine($from, $to, $oldFrom = false)
    {
        $answer = array('error' => false, 'text' => '');

        if (isset($this->redirects[$from])) {
            if ($this->redirects[$from]['to'] == $to) {
                $answer['text'] = "Такой уже редирект существует";
            } else {
                $answer['text'] = "Редирект с {$from} уже существует и переадресует на {$this->redirects[$from]['to']}";
            }
            $answer['error'] = true;
        }

        if (isset($this->redirects[$to]) && !$answer['error']) {
            if ($this->redirects[$to]['to'] == $from) {
                $answer['text'] = "Организуется бесконечный редирект с {$from} на {$to} потом {$from}. Бесконечный цикл";
            } else {
                $answer['text'] = "Организуется множественный редирект с {$from} на {$to}, а потом на {$this->redirects[$to]['to']}";
            }
            $answer['error'] = true;
        }

        if (!$answer['error']) {
            if ($oldFrom) {
                // Если нужно заменить элемент — указываем куда его поставить
                $_arr = array();
                foreach($this->redirects as $k => $v) {
                    if ($k === $oldFrom) $k = $from;
                    $_arr[$k] = $v;
                }
                $this->redirects = $_arr;
            } else {
                // Нужно просто добавить элемент
                $this->redirects[$from]['to'] = $to;
            }
            $this->saveFile();
        }
        print json_encode($answer);
        exit;

    }

    /**
     * Редактирование редиректа
     *
     * @param string $from
     * @param string $to
     * @param string $oldFrom
     * @param string $oldTo
     */
    public function editLine($from, $to, $oldFrom, $oldTo)
    {
        $answer = array('error' => false, 'text' => '');

        if (($from == $oldFrom) && ($to == $oldTo)) {
            // Ничего не изменилось, просто перезаписываем файлы редиректов (нужно для разрешения конфликтов)
            $this->saveFile();
            print json_encode($answer);
            exit;
        }

        if ($from != $oldFrom) {
            // Если изменился и адрес from — используем метод добавления редиректа
            // if (isset($this->redirects[$oldFrom])) unset($this->redirects[$oldFrom]);
            $this->addLine($from, $to, $oldFrom);
            exit;
        }

        if (isset($this->redirects[$to]['to'])) {
            $answer['text'] = "Организуется множественный редирект с {$from} на {$to}, а потом на {$this->redirects[$to]['to']}";
            $answer['error'] = true;
        }

        if (($to == $from) || (isset($this->redirects[$to]['to']) && ($this->redirects[$to]['to'] == $from))) {
            $answer['text'] = "Организуется бесконечный редирект с {$from} на {$to} потом {$from}. Бесконечный цикл";
            $answer['error'] = true;
        }

        $this->redirects[$from]['to'] = $to;

        if (!$answer['error']) {
            $this->saveFile();
        }

        print json_encode($answer);
        exit;
    }

    /**
     * Удаление редиректа $from
     *
     * @param string $from
     * @param string $to
     */
    public function deleteLine($from, $to)
    {
        $answer = array('error' => false, 'text' => '');
        if (isset($this->redirects[$from])) {
            if ($this->redirects[$from]['to'] == $to) {
                unset($this->redirects[$from]);
            } else {
                // todo не понял, когда такой случай возможен
                $answer['error'] = true;
                $answer['text'] = "Редирект с {$from} на {$this->redirects[$from]['to']}.";
            }
        }
        if (!$answer['error']) {
            $this->saveFile();
        }
        print json_encode($answer);
        exit;
    }

    /**
     * Сохраняет изменения в редиректах в файлы
     * Сбрасываются все конфликты. Используется первый срабатывающий редирект,
     * предпочтение отдаётся редиректам из файла redirect.txt
     */
    private function saveFile()
    {
        // Запись в redirect.txt
        $file = "#redirect#\n";
        foreach ($this->redirects as $k => $v) {
            $file .= "RewriteRule {$k} {$v['to']} [R=301,L]\n";
        }
        $file .= "#redirect#";
        file_put_contents($this->reFile, $file);

        // Запись в htaccess
        $t = file_get_contents(DOCUMENT_ROOT . '/.htaccess');
        $file = str_replace('\\', '\\\\', $file); // заменяем один слэш на два (экранируем)
        $file = str_replace('$', '\$', $file); // экранируем символ $
        $file = preg_replace('/\#redirect\#(.*)\#redirect\#/s', $file, $t);
        file_put_contents($this->htFile, $file);

        return true;
    }

    /**
     * Выводит уже существующие редиректы в виде строки таблицы
     * @return string Html-таблица с редиректами
     */
    public function getTable()
    {
        $str = '';
        $i = 1;
        foreach ($this->redirects as $from => $v) {
            $class = $info = '';
            $defaultFrom = "data-from='{$from}'";
            $defaultTo = "data-to='{$v['to']}'";

            if (isset($v['htaccess'])) {
                // Если редиректы в htaccess отличаются от redirect.txt
                $class = "class='error'";
                if ($v['to'] != $v['htaccess']['to']) {
                    $v['error'] .= ".htaccess: указан редирект на {$v['htaccess']['to']}<br />";
                }
                if ($v['htaccess']['error']) {
                    $v['error'] .= $v['htaccess']['error'];
                }
            }

            if ($v['error']) {
                // Если есть ошибки, оформляем их список
                $class = ($class == '') ? "class='warning'" : $class;
                $info = '<small>' . $v['error'] . '</small>';
            }

            /*
            switch ($v['error']):
                case 1:
                    $class = "class='error'";
                    $defaultTo = '';
                    $info = '<br/>htaccess: ' . $v['htaccess']['to'];
                    $this->msg .= "<div class='alert alert-error'>" .
                        "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                        "Разница в редиректах. Необходимо исправить. <a href='#line{$i}'>Подробнее</a></div>";
                    break;
                case 2:
                    $defaultTo = '';
                    $defaultFrom = '';
                    $class = "class='warning'";
                    $this->msg .= "<div class='alert alert-block'>" .
                        "<button type='button' class='close' data-dismiss='alert'>&times;</button>" .
                        "Удаленный редирект. Необходимо исправить. " . "
                        <a href='#' onclick=\"scrollToElement('line{$i}'); return false;\">Подробнее</a></div>";
                    break;
                case 3:
                    break;
            endswitch;
            */
            $str .= <<<RULE
            <tr id="line{$i}" {$class}>
<td class="from" {$defaultFrom}>{$from}</td><td><div class="to" {$defaultTo}>{$v['to']}</div>{$info}</td>
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
