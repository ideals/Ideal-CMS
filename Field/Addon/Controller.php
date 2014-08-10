<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Addon;

use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Field\AbstractController;

/**
 * Подключение addon'ов к элементу структуры сайта
 *
 * Отображается в виде скрытого поля ввода <input type="hidden" />, в котором содержится список подключённых
 * к этому элементу addon'ов, кнопки редактирования списка подключённых addon'ов (рядом со списком вкладок)
 * и окна редактирования списка addon'ов, которое появляется при нажатии на эту кнопку.
 *
 * Список addon'ов хранится в json-массиве.
 *
 * ВАЖНО! У каждого элемента структуры может быть только одно поле Ideal_Addon.
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'addon' => array(
 *         'label' => "Список подключённых аддонов",
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_Addon'
 *     ),
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        $input = $this->getInputText();
        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = $this->getValue();

        // Формируем содержание выпадающего окна редактирования вкладок
        $editHtml = '<div id="addonsList"></div>'
            . $this->getAvailableAddonsList()
            . '<button class="btn btn-primary">Сохранить</button>'
            . '<button class="btn btn-default" onclick="$(\'#tabsModal\').toggle()">Отменить</button>';

        $editHtml = strtr($editHtml, array("\n" => ''));
        $editHtml = addcslashes($editHtml, "'");

        // Получаем список доступных аддонов
        $availableAddons = htmlspecialchars(json_encode($this->field['available']));

        $tabs = $this->getTabs($value);

        $valueHtml = htmlspecialchars($value);

        $html = <<<HTML
            <input type="hidden" id="{$this->htmlName}" name="{$this->htmlName}" value="{$valueHtml}">
            <input type="hidden" id="available_addons" name="available_addons" value="{$availableAddons}">
            <script type="text/javascript">
            function getAddonFieldName() {
                return "{$this->htmlName}";
            }
            $(document).ready(function() {
                // Добавляем контент во всплывающее окно редактирования вкладок
                $('#tabsModal').html('{$editHtml}');

                // Включаем кнопку редактирования вкладок
                $('#modalTabsEdit').removeClass('hide');

                // Добавляем вкладки к списку вкладок
                $('#tabs').append('{$tabs['names']}');

                // Добавляем собственно само содержимое вкладок
                $('#tabs-content').append('{$tabs['contents']}');
            });
            </script>
            <script type="text/javascript" src="Ideal/Field/Addon/script.js"></script>
HTML;
        return $html;
    }

    /**
     * Получение списка подключённых аддонов для их редактирования
     *
     * @return string
     */
    public function getAvailableAddonsList()
    {
        // Скрываем выбор добавляемого аддона за кнопкой +
        $html = '<button id="add-addon-button" class="btn btn-default">+</button>'
              . '<div id="add-addon" class="input-group hide">'
              . '<select class="form-control" name="' . $this->htmlName . '1" id="' . $this->htmlName . '1">'
              . '';

        // Получаем список доступных для добавления аддонов для этого элемента
        $className = $this->field['medium'];
        /** @var \Ideal\Medium\AbstractModel $medium */
        $medium = new $className($this->model, $this->name);
        $list = $medium->getList();

        foreach ($list as $k => $v) {
            $html .= '<option value="' . $k . '">' . $v . '</option>';
        }
        $html .= '</select>';

        // Кнопка добавления аддона, после его выбора в select
        $html .= '<span class="input-group-btn">'
            . '<button type="button" id="add-addon-add" class="btn btn-default">+</button>'
            . '<button type="button" id="add-addon-hide" class="btn btn-default">&times;</button>'
            . '</span>'
            . '</div>';

        return $html;
    }

    /**
     * Получения содержимого всех вкладок для первоначального отображения
     *
     * @param $json
     * @return array
     */
    public function getTabs($json)
    {
        $arr = json_decode($json);
        $result = array(
            'names' => '',
            'contents' => ''
        );

        foreach ($arr as $v) {
            $tabId = $v[0];
            $addonVar = $v[1];
            $addonName = $v[2];

            // Получаем название и содержимое вкладки
            $tab = $this->getTab($tabId, $addonVar);

            // Записываем заголовок вкладки
            $result['names'] .= addslashes(
                "<!--suppress HtmlUnknownAnchorTarget -->"
                . "<li><a data-toggle=\"tab\" id=\"tab{$addonVar}Head\" href='#tab{$addonVar}'>{$addonName}</a>"
                . "</li>"
            );

            // Записываем содержимое вкладки
            $result['contents'] .= addslashes("<div id=\"tab{$addonVar}\" class=\"tab-pane\">{$tab['content']}</div>");
        }

        return $result;
    }

    /**
     * Получение названия и содержимого одной вкладки
     *
     * @param  integer $id Идентификатор вкладки
     * @param string $addonName Название аддона
     * @return array
     */
    protected function getTab($id, $addonName)
    {
        return array('name' => $addonName, 'content' => $id);
    }

    /**
     * Переопределил, чтобы на начальном этапе с бд не возиться
     * @return string
     */
    public function getValue()
    {
        // todo убрать, когда заработает редактирование аддонов
        return json_encode(array(
            array(1, 'Ideal_Page', 'Текст'),
            array(2, 'Ideal_Php', 'Php-файл'),
        ));
    }
}
