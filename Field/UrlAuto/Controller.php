<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\UrlAuto;

use Ideal\Field\Url;

/**
 * Поле, содержащее финальный сегмент URL для редактируемого элемента
 *
 * Отличается от своего предка тем, что визуальная часть содержит кнопку для включения/отключения
 * автоматической генерации URL на основе названия элемента.
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'url' => array(
 *         'label' => 'URL',
 *         'sql'   => 'varchar(255) not null',
 *         'type'  => 'Ideal_UrlAuto',
 *         'nameField' => 'name' // имя поля по которому происходит генерация url
 *     ),
 */
class Controller extends Url\Controller
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $url = new Url\Model();
        $value = array('url' => htmlspecialchars($this->getValue()));
        $link = $url->getUrlWithPrefix($value, $this->model->getParentUrl());
        $link = $url->cutSuffix($link);
        // Проверяем, является ли url этого объекта частью пути
        $addOn = '';
        if (($link !== '') && ($link{0} == '/') && ($value != $link)) {
            // Выделяем из ссылки путь до этого объекта и выводим его перед полем input
            $path = substr($link, 0, strrpos($link, '/'));
            $addOn = '<span class="input-group-addon">' . $path . '/</span>';
        }
        $nameField = 'name';
        if (isset($this->field['nameField'])) {
            $nameField = $this->field['nameField'];
        }
        return
            '<script type="text/javascript" src="Ideal/Field/UrlAuto/admin.js" />'
            . '<div class="input-group">' . $addOn
            . '<input type="text" class="form-control" name="' . $this->htmlName . '" id="' . $this->htmlName
            . '" value="' . $value['url'] . '" data-field="' . $nameField . '"><span class="input-group-btn">'
            . '<button id="UrlAuto" type="button" class="btn btn-danger" onclick="setTranslit(this)">'
            . 'auto url off</button>'
            . '</span></div>';
    }
}
