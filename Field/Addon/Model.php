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

class Model
{
    /** @var  string Название поля, используемое для полей ввода в html-коде */
    public $htmlName;

    /** @var  array Параметры поля, взятые из конфигурационного файла структуры */
    protected $field;

    /** @var  string Название вкладки, в которой находится поле в окне редактирования */
    protected $groupName;

    /** @var  \Ideal\Core\Admin\Model Модель данных, в которой находится редактируемое поле */
    protected $model;

    /** @var  string Название поля */
    protected $name;

    /**
     * Получение списка подключённых аддонов для их редактирования
     *
     * @return string
     */
    public function getAvailableAddonsList()
    {
        // Скрываем выбор добавляемого аддона за кнопкой +
        $html = '<button id="add-addon-button" class="btn btn-info">Добавить аддон</button>'
            . '<div id="add-addon" class="list-group input-group hide">'
            . '<select class="form-control" name="add-addon-select" id="add-addon-select">';

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

            // Получаем название, заголовок и содержимое вкладки
            $tab = $this->getTab($tabId, $addonVar, $addonName);

            // Записываем заголовок вкладки
            $result['names'] .= addslashes($tab['header']);

            $tab['content'] = str_replace(
                array("\\", '<script>', '/', "'"),
                array("\\\\", '\<script>', '\/', "\\'"),
                $tab['content']
            );

            // Убираем переводы строки, иначе текст не обрабатывается в JS
            $tab['content'] = str_replace(array("\n\r", "\r\n", "\n", "\r"), '\\n', $tab['content']);

            // Записываем содержимое вкладки
            $result['contents'] .= $tab['content'];
        }

        return $result;
    }

    /**
     * Получение названия и содержимого одной вкладки
     *
     * @param  integer $id Идентификатор вкладки
     * @param string $addonVar Название аддона
     * @param string $addonName Наименование вкладки аддона
     * @return array
     */
    public function getTab($id, $addonVar, $addonName = '')
    {
        $class = Util::getClassName($addonVar, 'Addon') . '\\AdminModel';
        /** @var \Ideal\Core\Admin\Model $model */
        $model = new $class('');

        // Получаем тип аддона для формирования правильного groupName
        $groupName = explode('_', $addonVar);
        $groupName = strtolower(end($groupName));
        $model->setFieldsGroup($groupName . '-' . $id);

        // Если создаётся новая вкладка то данные из связанного объекта не нужны
        $pageData = $this->model->getPageData();
        if (isset($pageData['ID'])) {
            // Загрузка данных связанного объекта
            $config = Config::getInstance();
            $path = $this->model->getPath();
            $end = end($path);
            $prevStructure = $config->getStructureByName($end['structure']);
            $prevStructure = $prevStructure['ID'] . '-' . $pageData['ID'];
            $model->setPageDataByPrevStructure($prevStructure);
        }

        // Принудительно устанавливаем tab_ID, т.к. при добавлении аддона его может и не быть
        $pageData = $model->getPageData();
        $pageData['tab_ID'] = $id;
        $model->setPageData($pageData);

        $addonVar .= '_' . $id;
        $addonName = ($addonName == '') ? $model->params['name'] : $addonName; // если почему-то в БД сбросится

        $tab = "<!--suppress HtmlUnknownAnchorTarget -->" // это чтобы PhpStorm не ругался на href='#tab{$addonVar}'
            . "<li><a data-toggle=\"tab\" id=\"tab{$addonVar}Head\" href=\"#tab{$addonVar}\">{$addonName}</a>"
            . "</li>";

        // Получение содержимого вкладки
        $tabContent = $model->getFieldsList($model->fields);

        // Оборачиваем в div вкладки
        $tabContent = "<div id=\"tab{$addonVar}\" class=\"tab-pane\">{$tabContent}</div>";

        $result = array(
            'name' => $addonName,
            'header' => $tab,
            'content' => $tabContent
        );

        return $result;
    }

    /**
     * Установка модели редактируемого объекта, частью которого является редактируемое поле
     *
     * Полю необходимо получать сведения о состоянии объекта и о других полях, т.к.
     * его значения и поведение может зависеть от значений других полей
     *
     * @param \Ideal\Core\Admin\Model $model     Модель редактируемого объекта
     * @param string                  $fieldName Редактируемое поле
     * @param string                  $groupName Вкладка, к которой принадлежит редактируемое поле
     */
    public function setModel($model, $fieldName, $groupName = 'general')
    {
        $this->name = $fieldName;
        $this->model = $model;
        $this->field = $model->fields[$fieldName];
        $this->groupName = $groupName;
        $this->htmlName = $this->groupName . '_' . $this->name;
    }
}
