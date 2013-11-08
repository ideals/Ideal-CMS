<?php
namespace Ideal\Field\Template;

use Ideal\Core\Config;
use Ideal\Field\Select;
use Ideal\Core\Request;
use Ideal\Core\Util;

class Controller extends Select\Controller
{
    protected static $instance;


    public function getInputText()
    {
        $modelName = $this->getValue();
        $class =  Util::getClassName($modelName, 'Template') . '\\Model';
        $model = new $class('');
        $model->setFieldsGroup($this->name);
        // Загрузка данных связанного объекта
        $id = '';
        $pageData = $this->model->getPageData();
        if (isset($pageData['ID'])) {
            $config = Config::getInstance();
            $prevStructure = $config->getStructureByName($pageData['structure']);
            $prevStructure = $prevStructure['ID'] . '-' . $pageData['ID'];
            $model->setPageDataByprevStructure($prevStructure);
            $id = $pageData['ID'];
        }
        // Получение содержимого таба
        $tabContent = $model->getFieldsList($model->fields, $this->name);
        // Убираем переводы строки, иначе текст не обрабатывается в JS
        $tabContent = str_replace(array("\n\r", "\r\n", "\n", "\r"), '\\n', $tabContent);
        $tabContent = str_replace(array('<script>', '</script>', "'"), array('\<script>', '<\/script>', "\\'"), $tabContent);
        $html = parent::getInputText();
        // Добавляем обработчик на изменение select
        $html = str_replace('<select', '<select onchange="changeTemplate(this, \'' . $this->name . '\')"', $html);
        $tabName = $this->list[$this->getValue()];
        $html .= "<script>
            $(document).ready(function() {
                // Добавляем элемент к списку табов
                data = '<li><a data-toggle=\"tab\" id=\"tab{$this->name}Head\" href=\"#tab{$this->name}\">{$tabName}</a></li>';
                $('#tabs').append(data);

                // Добавляем собственно саму страничку таба
                data = '<div id=\"tab{$this->name}\" class=\"tab-pane\">{$tabContent}</div>';
                $('#tabs-content').append(data);
            });
            </script>";
        $request = new Request();
        $par = $request->par;
        $action = $request->action . "Template";
        $html .= "<script>
            function changeTemplate(tab, name){
                // Заменяем текст заголовка таба
                w = tab.selectedIndex;
                selectedText = tab.options[w].text;
                $('#tab' + name + 'Head').html(selectedText);
                // Заменяем внутренности таба с полями шаблона
                url = 'index.php?par={$par}&action={$action}&id={$id}&template=' + tab.value + '&name=' + name;
                $('#tab' + name).load(url);
            }</script>";
        return $html;
    }


    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // TODO Дли типа данных шаблон - нужно распарсить его элементы
        $templateName = Util::getClassName($this->newValue, 'Template') . '\\Model';
        /* @var $template \Ideal\Core\Admin\Model */
        $template = new $templateName('не имеет значения, т.к. только парсим ввод юзера');
        $template->setFieldsGroup($this->name);
        $item['items'] = $template->parseInputParams($isCreate);

        return $item;
    }

}