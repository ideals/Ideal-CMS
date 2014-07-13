<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Template;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Field\Select;

/**
 * Специальное поле, предоставляющее возможность добавить к редактируемому элементу дополнительные поля
 *
 * Эти дополнительные поля хранятся в отдельной таблице и связаны с редактируемым элементом
 * через prev_structure.
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'template' => array(
 *         'label'     => 'Тип документа',
 *         'sql'       => "varchar(30) not null default 'Ideal_Page'",
 *         'type'      => 'Ideal_Template',
 *         'medium'    => '\\Ideal\\Medium\\TemplateList\\Model',
 *         'templates' =>  array('Ideal_Page', 'Ideal_SimpleNote', 'Ideal_PhpFile', 'Ideal_SiteMap'),
 *     ),
 *
 * В поле medium указывается класс, отвечающий за предоставление списка элементов для select.
 * Кроме того Ideal_Template, при выборе одного из значений в списке, добавляет вкладку, в которой
 * находятся поля таблицы выбранного Template.
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
        $modelName = $this->getValue();
        $class = Util::getClassName($modelName, 'Template') . '\\Model';
        $model = new $class('');
        $model->setFieldsGroup($this->name);
        // Загрузка данных связанного объекта
        $id = '';
        $pageData = $this->model->getPageData();
        if (isset($pageData['ID'])) {
            $config = Config::getInstance();
            $path = $this->model->getPath();
            $end = end($path);
            $prevStructure = $config->getStructureByName($end['structure']);
            $prevStructure = $prevStructure['ID'] . '-' . $pageData['ID'];
            $model->setPageDataByPrevStructure($prevStructure);
            $id = $pageData['ID'];
        }
        // Получение содержимого вкладки
        $tabContent = $model->getFieldsList($model->fields);
        // Убираем переводы строки, иначе текст не обрабатывается в JS
        $tabContent = str_replace(array("\n\r", "\r\n", "\n", "\r"), '\\n', $tabContent);
        $tabContent = str_replace(
            array('<script>', '</script>', "'"),
            array('\<script>', '<\/script>', "\\'"),
            $tabContent
        );
        $html = parent::getInputText();
        // Добавляем обработчик на изменение select
        $html = str_replace('<select', '<select onchange="changeTemplate(this, \'' . $this->name . '\')"', $html);
        $tabName = $this->list[$this->getValue()];
        $html .= "<script>
            $(document).ready(function() {
                // Добавляем элемент к списку вкладок
                var data = '<li>'
                     + '<a data-toggle=\"tab\" id=\"tab{$this->name}Head\" href=\"#tab{$this->name}\">{$tabName}</a>'
                     + '</li>';
                $('#tabs').append(data);

                // Добавляем собственно саму страничку вкладки
                data = '<div id=\"tab{$this->name}\" class=\"tab-pane\">{$tabContent}</div>';
                $('#tabs-content').append(data);
            });
            </script>";
        $request = new Request();
        $par = $request->par;
        $action = $request->action . "Template";
        $html .= "<script>
            function changeTemplate(tab, name){
                // Заменяем текст заголовка вкладки
                var w = tab.selectedIndex;
                var selectedText = tab.options[w].text;
                $('#tab' + name + 'Head').html(selectedText);
                // Заменяем внутренности вкладки с полями шаблона
                var url = 'index.php?par={$par}&action={$action}&id={$id}&template=' + tab.value + '&name=' + name;
                $('#tab' + name).load(url);
            }</script>";
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // TODO Дли типа данных шаблон - нужно распарсить его элементы
        $templateName = Util::getClassName($this->newValue, 'Template') . '\\Model';
        /* @var $template \Ideal\Core\Admin\Model */
        $template = new $templateName('не имеет значения, т.к. только парсим ввод пользователя');
        $template->setFieldsGroup($this->name);
        $item['items'] = $template->parseInputParams($isCreate);

        return $item;
    }
}
