<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\SiteData;

use Ideal\Core\Util;

/**
 * Чтение, отображение и запись специального формата конфигурационных php-файлов
 */
class ConfigPhp
{
    /** @var array Массив для хранения считанных данных из php-файла */
    protected $params = array();

    /**
     * Считывание данных из php-файла
     *
     * @param string $fileName Имя php-файла из которого читается конфигурация
     */
    public function loadFile($fileName)
    {
        $cfg = file($fileName);
        $skip = array('<?php', 'return array(', ');');

        $params['default'] = array(
            'arr' => array(),
            'name' => 'Основное'
        );

        $c = count($cfg);
        // Проходимся по всем строчкам php-файла и заполняем массив $params
        for ($i = 0; $i < $c; $i++) {
            $v = $cfg[$i];
            if (in_array(trim($v), $skip)) {
                continue;
            }
            list ($other, $label) = explode("', // ", $v);
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
     * Сохранение обработанных конфигурационных данных в файл
     * @param string $fileName Название php-файла, в который сохраняются данные
     */
    public function saveFile($fileName)
    {
        // Изменяем постоянные настройки сайта
        $file = "<?php\nreturn array(\n";
        foreach ($this->params as $tabId => $tab) {
            $pad = 4;
            if ($tabId != 'default') {
                $file .= "    '{$tabId}' => array( // {$tab['name']}\n";
                $pad = 8;
            }
            foreach ($tab['arr'] as $field => $param) {
                $fieldName = $tabId . '_' . $field;
                $model = new mockModel();
                $model->fields[$fieldName] = $param;
                $model->pageData[$fieldName] = $param['value'];

                $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
                /** @var $fieldModel \Ideal\Field\AbstractController */
                $fieldModel = $fieldClass::getInstance();
                $fieldModel->setModel($model, $fieldName, 'general');

                $value = $fieldModel->pickupNewValue();
                if ($param['type'] == 'Ideal_Area') {
                    $value = str_replace("\r", '', $value);
                    $value = str_replace("\n", '\n', $value);
                }

                $options = (defined('JSON_UNESCAPED_UNICODE')) ? JSON_UNESCAPED_UNICODE : 0;
                $values = ($param['type'] == 'Ideal_Select') ? ' | ' . json_encode($param['values'], $options) : '';

                $file .= str_repeat(' ', $pad) . "'" . $field . "' => '" . $value
                    . "', // " . $param['label'] . ' | ' . $param['type'] . $values . "\n";
            }
            if ($tabId != 'default') {
                $file .= "    ),\n";
            }
        }

        $file .= ");\n";
        $fp = fopen($fileName, 'w');
        if (fwrite($fp, $file)) {
            $this->loadFile($fileName);
            print <<<DONE
<script type="text/javascript">
        var text = '<div class="alert alert-block alert-success fade in">'
                 + '<button type="button" class="close" data-dismiss="alert">&times;</button>'
                 + '<span class="alert-heading">Настройки сохранены!</span></div>';
        $("form").prepend(text);
</script>
DONE;
        }
    }

    /**
     * Отображение считанных конфигурационных данных в виде полей ввода с подписями
     * @return string Сгенерированный HTML-код
     */
    public function showEdit()
    {
        $tabs = '<ul class="nav nav-tabs">';
        $tabsContent = '<div class="tab-content">';
        foreach ($this->params as $tabId => $tab) {
            $active = ($tabId == 'default') ? 'active' : '';
            $tabs .= '<li class="' . $active . '">'
                   . '<a href="#' .$tabId . '" data-toggle="tab">' . $tab['name'] . '</a>'
                   . '</li>';
            $tabsContent .= '<div class="tab-pane well ' . $active . '" id="' . $tabId . '">';
            foreach ($tab['arr'] as $field => $param) {
                $fieldName = $tabId . '_' . $field;
                $model = new mockModel();
                $model->fields[$fieldName] = $param;
                $model->pageData[$fieldName] = $param['value'];

                $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
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


class mockModel
{
    public $pageData;
    public $fields;

    public function getPageData()
    {
        return $this->pageData;
    }

}
