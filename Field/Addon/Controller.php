<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Addon;

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

        $addonModel = new Model();
        $addonModel->setModel($this->model, $this->name, $this->groupName);

        // Формируем содержание выпадающего окна редактирования вкладок
        $editHtml = '<div id="addonsList"></div>'
            . $addonModel->getAvailableAddonsList()
            . '<button class="btn btn-link" onclick="$(\'#tabsModal\').toggle()">Закрыть</button>';

        $editHtml = strtr($editHtml, array("\n" => ''));
        $editHtml = addcslashes($editHtml, "'");

        // Получаем список доступных аддонов
        $availableAddons = htmlspecialchars(json_encode($this->field['available']));

        $tabs = $addonModel->getTabs($value);

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
     * {@inheritdoc}
     */
    public function getValue()
    {
        $value = parent::getValue();

        // Восстановление названия подключённых аддонов, т.к. по умолчанию текстовое название
        // аддона не указывается.

        $arr = json_decode($value);

        foreach ($arr as $k => $v) {
            $addonVar = $v[1];
            $addonName = $v[2];

            $class = Util::getClassName($addonVar, 'Addon') . '\\Model';

            /** @var \Ideal\Core\Admin\Model $model */
            $model = new $class('');
            $arr[$k][2] = ($addonName == '') ? $model->params['name'] : $addonName; // если почему-то в БД сбросится
        }

        $value = json_encode($arr);

        return $value;
    }

  // Получаем данные из полей аддонов
  public function parseInputValue($isCreate)
  {
    $item = parent::parseInputValue($isCreate);

    //Проходим по каждому аддону и собираем введённую информацию
    $addonNewValues = json_decode($this->newValue);
    foreach($addonNewValues as $addonNewValue) {
      $addonName = Util::getClassName($addonNewValue[1],
          'Addon') . '\\Model';
      $addon = new $addonName('не имеет значения, т.к. только парсим ввод пользователя');
      $addon->setFieldsGroup($this->name);
      $addon->setHtmlNameModifier($addonNewValue[0]);
      $item['items']['addons'][$addonNewValue[0]] = $addon->parseInputParams($isCreate);
    }

    return $item;
  }
}
