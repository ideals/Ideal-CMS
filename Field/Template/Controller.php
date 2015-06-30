<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Template;

use Ideal\Field\Select;
use Ideal\Core\Request;

/**
 * Специальное поле, предоставляющее возможность выбрать шаблон для отображения структуры
 *
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'template' => array(
 *         'label' => 'Шаблон отображения',
 *         'sql' => "varchar(255) default 'index.twig'",
 *         'type' => 'Ideal_Template',
 *         'medium' => '\\Ideal\\Medium\\TemplateList\\Model',
 *         'default'   => 'index.twig',
 *
 * В поле medium указывается класс, отвечающий за предоставление списка элементов для select.
 */
class Controller extends Select\Controller
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {

        $html = '';

        // Подключаем скрипт смены списка шабонов, только если доступна более чем одна структура
        if (count($this->list) > 1) {
            $html .= '<script type="text/javascript" src="Ideal/Field/Template/templateShowing.js" />';
        }

        // Получаем значение поумолчанию для структуры
        $pageData = $this->model->getPageData();
        if (isset($pageData['structure']) && !empty($pageData['structure'])) {
            $structureValue = $pageData['structure'];
        } else {
            reset($this->list);
            $structureValue = key($this->list);
        }

        // Составляем списки шаблонов
        foreach ($this->list as $key => $value) {
            // индикатор показа списка по умолчанию
            $structureValue == $key ? $display = "block" : $display = "none";

            // Построение тега "select" со списком доступных шаблонов
            $html .= '<input type="text" class="form-control" name="' . $this->htmlName . '_' . strtolower($key) . '" id="' . $this->htmlName . '_' . strtolower($key) . '" value="' . $this->getValue() . '"/>';
            $availableTemplates = array();
            foreach ($value as $v) {
                $availableTemplates[] = $v;
            }

            $html .= '
            <script>
                $(function() {
                    var availableTemplates_' . strtolower($key) . ' = [
                        "' . implode('",
                        "', $availableTemplates) . '"
                    ];
                    $("#' . $this->htmlName . '_' . strtolower($key) . '").autocomplete({
                        source: availableTemplates_' . strtolower($key) . '
                    });
                });
            </script>
            <script>
            // Скрываем лишние теги "input".
            $(\'.general_template-controls input[name="general_template_' . strtolower($key) . '"]\').css("display",
            "' . $display . '");
            </script>';

        }
        return $html;
    }

    /**
     * Получение нового значения поля шаблона из данных, введённых пользователем, с учётом "Типа раздела"
     *
     * @return string
     */
    public function pickupNewValue()
    {
        $request = new Request();

        // Вычисляем последний префикс с учётом того что поля выбора типа структуры может не существовать
        if (isset($request->general_structure)) {
            $lastPrefix = strtolower($request->general_structure);
        } else {
            $objClassName = get_class($this->model);
            $objClassNameSlice = explode('\\', $objClassName);
            $lastPrefix = strtolower($objClassNameSlice[0] . '_' . $objClassNameSlice[2]);
        }

        $fieldName = $this->groupName . '_' . $this->name . '_' . $lastPrefix;

        // Получаем название файла, так как полный путь используется только для удобства представления
        $fileName = end(explode('/', $request->$fieldName));
        $this->newValue = $fileName;
        return $this->newValue;
    }
}
