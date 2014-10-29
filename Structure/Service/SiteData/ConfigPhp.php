<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\SiteData;

use Ideal\Core\Util;

/**
 * Чтение, отображение и запись специального формата конфигурационных php-файлов
 * todo написать магические геттеры и сеттеры, чтобы переменные конфига можно было изменять без обращения к массивам
 */
class ConfigPhp
{

    /** @var array Массив для хранения считанных данных из php-файла */
    protected $params = array();

    /**
     * Геттер для защищённого поля $params
     * @return array Набор считанных из файла параметров
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Замена значений настроек в $this->params на данные, введённые пользователем
     */
    public function pickupValues()
    {
        $pageData = array();
        foreach ($this->params as $tabId => $tab) {
            foreach ($tab['arr'] as $field => $param) {
                $fieldName = $tabId . '_' . $field;
                $model = new MockModel('');
                $model->fields[$fieldName] = $param;
                $pageData[$fieldName] = $param['value'];
                $model->setPageData($pageData);

                $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
                /** @noinspection PhpUndefinedMethodInspection  */
                /** @var $fieldModel \Ideal\Field\AbstractController */
                $fieldModel = $fieldClass::getInstance();
                $fieldModel->setModel($model, $fieldName, 'general');

                // Получаем данные от пользователя
                $value = $fieldModel->pickupNewValue();

                // Экранируем переводы строки для сохранения в файле
                $value = str_replace("\r", '', $value);
                $value = str_replace("\n", '\n', $value);

                $this->params[$tabId]['arr'][$field]['value'] = $value;
            }
        }

    }

    /**
     * Сохранение обработанных конфигурационных данных в файл
     *
     * @param string $fileName Название php-файла, в который сохраняются данные
     * @return int Возвращает количество записанных в файл байт или false
     */
    public function saveFile($fileName)
    {
        // Изменяем постоянные настройки сайта
        $file = "<?php\n// @codingStandardsIgnoreFile\nreturn array(\n";
        foreach ($this->params as $tabId => $tab) {
            $pad = 4;
            if ($tabId != 'default') {
                $file .= "    '{$tabId}' => array( // {$tab['name']}\n";
                $pad = 8;
            }
            foreach ($tab['arr'] as $field => $param) {
                $options = (defined('JSON_UNESCAPED_UNICODE')) ? JSON_UNESCAPED_UNICODE : 0;
                $values = ($param['type'] == 'Ideal_Select') ? ' | ' . json_encode($param['values'], $options) : '';

                $file .= str_repeat(' ', $pad) . "'" . $field . "' => '" . $param['value']
                    . "', // " . $param['label'] . ' | ' . $param['type'] . $values . "\n";
            }
            if ($tabId != 'default') {
                $file .= "    ),\n";
            }
        }

        $file .= ");\n";

        return file_put_contents($fileName, $file);
    }

    /**
     * Изменение настроек на введённые пользователем и сохранение их в файл
     *
     * @param string $fileName Название php-файла, в который сохраняются данные
     * @return bool Флаг успешности сохранения данных в файл
     */
    public function changeAndSave($fileName)
    {
        $class = 'alert';
        $res = true;
        $text = 'Настройки сохранены!';

        // Заменяем настройки на введённые пользователем
        $this->pickupValues();

        if ($this->saveFile($fileName) === false) {
            $res = false;
            $text = 'Не получилось сохранить настройки в файл ' . $fileName;
        }

        print <<<DONE
        <div class="{$class} {$class}-block {$class}-success fade in">
        <button type="button" class="close" data-dismiss="{$class}">&times;</button>
        <span class="{$class}-heading">{$text}</span></div>
DONE;
        return $res;
    }

    /**
     * Считывание данных из php-файла
     *
     * @param string $fileName Имя php-файла из которого читается конфигурация
     * @return bool Флаг успешного считывания данных из файла
     */
    public function loadFile($fileName)
    {
        if (!file_exists($fileName)) {
            return false;
        }

        $cfg = file($fileName);
        $skip = array(
            '<?php',
            '// @codingStandardsIgnoreFile',
            'return array(',
            ');'
        );

        $params['default'] = array(
            'arr' => array(),
            'name' => 'Основное'
        );

        $c = count($cfg);
        // Проходимся по всем строчкам php-файла и заполняем массив $params
        for ($i = 0; $i < $c; $i++) {
            $v = $cfg[$i];
            $v = preg_replace("/[\t]+/", '    ', $v);
            if (in_array(trim($v), $skip)) {
                continue;
            }
            $cols = explode("', // ", $v);
            $other = $cols[0];
            $label = isset($cols[1]) ? $cols[1] : null;
            if (is_null($label)) {
                // Комментария в нужном формате нет, значит это массив
                preg_match('/\'(.*)\'\s*=>\s*array\s*\(\s*\/\/\s*(.*)/i', $other, $match);
                if (!isset($match[1]) || !isset($match[2])) {
                    echo "Ошибка парсинга файла {$fileName} в строке $i<br />";
                    exit;
                }
                $array = array();
                while (trim($cfg[++$i]) != '),') {
                    $v = $cfg[$i];
                    $param = $this->parseStr($v);
                    $array = array_merge($array, $param);
                }
                // Записываем массив данных в соответствующем формате
                $params[$match[1]] = array(
                    'arr' => $array,
                    'name' => $match[2]
                );
            } else {
                // Считываем и записываем переменную первого уровня
                $param = $this->parseStr($v);
                $params['default']['arr'] = array_merge($params['default']['arr'], $param);
            }
        }
        $this->params = $params;
        return true;
    }

    /**
     * Парсим одну строку конфига в массив данных
     *
     * @param string $str Строка конфига
     *
     * @return array
     */
    protected function parseStr($str)
    {
        list ($other, $label) = explode("', // ", $str);
        $label = chop($label);
        $fields = explode(' | ', $label);
        $label = $fields[0];
        $type = $fields[1];
        if ($type == '') {
            $type = 'Ideal_Text';
        }
        list ($name, $value) = explode(" => '", $other);
        $fieldName = trim($name, ' \''); // убираем стартовые пробелы и кавычку у названия поля
        $param[$fieldName] = array(
            'label' => $label,
            'value' => ($type == 'Ideal_Area') ? str_replace('\n', "\n", $value) : $value,
            'type' => $type
        );
        if ($type == 'Ideal_Select') {
            $param[$fieldName]['values'] = json_decode($fields[2]);
        }
        return $param;
    }

    /**
     * Сеттер для защищённого поля $this->params
     * @param array $params Модифицированный набор полей для сохранения в конфигурационном файле
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * Отображение считанных конфигурационных данных в виде полей ввода с подписями
     *
     * @return string Сгенерированный HTML-код
     */
    public function showEdit()
    {
        $tabs = '<ul class="nav nav-tabs">';
        $tabsContent = '<div class="tab-content">';
        foreach ($this->params as $tabId => $tab) {
            $active = ($tabId == 'default') ? 'active' : '';
            $tabs .= '<li class="' . $active . '">'
                . '<a href="#' . $tabId . '" data-toggle="tab">' . $tab['name'] . '</a>'
                . '</li>';
            $tabsContent .= '<div class="tab-pane well ' . $active . '" id="' . $tabId . '">';
            $pageData = array();
            foreach ($tab['arr'] as $field => $param) {
                $fieldName = $tabId . '_' . $field;
                $model = new MockModel('');
                $model->fields[$fieldName] = $param;
                $pageData[$fieldName] = $param['value'];
                $model->setPageData($pageData);

                $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
                /** @noinspection PhpUndefinedMethodInspection  */
                /** @var $fieldModel \Ideal\Field\AbstractController */
                $fieldModel = $fieldClass::getInstance();
                $fieldModel->setModel($model, $fieldName, 'general');
                $fieldModel->labelClass = '';
                $fieldModel->inputClass = '';
                $tabsContent .= $fieldModel->showEdit();
            }
            $tabsContent .= '</div>';
        }
        $tabs .= '</ul>';
        $tabsContent .= '</div>';
        if (count($this->params) == 1) {
            // Если вкладка только одна, то вкладки не надо отображать
            $tabs = '';
        }
        return $tabs . $tabsContent;
    }
}
