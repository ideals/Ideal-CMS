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
            $html .= '<select class="form-control" name="' . $this->htmlName . '_' . strtolower($key) . '" id="' . $this->htmlName . '_' . strtolower($key) . '" >';
            $defaultValue = $this->getValue();
            foreach ($value as $k => $v) {
                $selected = '';
                if ($k == $defaultValue) {
                    $selected = ' selected="selected"';
                }
                $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $html .= '</select>';

            // js скрипт инициализирующий модификацию тега "select" для возможности вставки собственного значения
            $html .= '
            <script>
            $(\'.general_template-controls select[name="general_template_' . strtolower($key) . '"]\').selectize({
                persist: false,
                create: function(input) {
                    return {
                        value: input,
                        text: input
                    }
                }
            });
            </script>
            <script>
            // Скрываем лишние теги "select" и следующие за ними теги "div" и открываем только одну пару.
            // Это необходимо потому что при инициализации js скрипта скрываются все теги select.
            // Из за этого едет вёрстка
            $(\'.general_template-controls select[name="general_template_' . strtolower($key) . '"]\').next("div").css("display",
            "' . $display . '");
            $(\'.general_template-controls select[name="general_template_' . strtolower($key) . '"]\').css("display",
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
        $this->newValue = $request->$fieldName;
        return $this->newValue;
    }
}
