<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
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
        // Подключаем скрипт смены списка шабонов, только если доступна более чем одна структура
        if (count($this->list) > 1) {
            $html = '<script type="text/javascript" src="Ideal/Field/Template/templateShowing.js" />';
        } else {
            $html = '';
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
            $structureValue == $key ? $display = "style='display: block;'" : $display = "style='display: none;'";
            $html .= '<select class="form-control" name="' . $this->htmlName . '_' . strtolower($key) . '" id="' . $this->htmlName . '_' . strtolower($key) . '" ' . $display . '>';
            $defaultValue = $this->getValue();
            foreach ($value as $k => $v) {
                $selected = '';
                if ($k == $defaultValue) {
                    $selected = ' selected="selected"';
                }
                $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $html .= '</select>';
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
        $fieldName = $this->groupName . '_' . $this->name . '_' . strtolower($request->general_structure);
        $this->newValue = $request->$fieldName;
        return $this->newValue;
    }
}
