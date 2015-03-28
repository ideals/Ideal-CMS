<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Url;

use Ideal\Field\AbstractController;
use Ideal\Core\Site;

/**
 * Поле, содержащее финальный сегмент URL для редактируемого элемента
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'url' => array(
 *         'label' => 'URL',
 *         'sql'   => 'varchar(255) not null',
 *         'type'  => 'Ideal_Url'
 *     ),
 */
class Controller extends AbstractController
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $url = new Model();
        $value = array('url' => htmlspecialchars($this->getValue()));
        $link = $url->getUrlWithPrefix($value, $this->model->getParentUrl());
        $link = $url->cutSuffix($link);
        // Проверяем, является ли url этого объекта частью пути
        $addOn = '';
        if (($link{0} == '/') && ($value != $link)) {
            // Выделяем из ссылки путь до этого объекта и выводим его перед полем input
            $path = substr($link, 0, strrpos($link, '/'));
            $addOn = '<span class="input-group-addon">' . $path . '/</span>';
        }
        return
            '<div class="input-group">' . $addOn
            . '<input type="text" class="form-control" name="' . $this->htmlName . '" id="' . $this->htmlName
            . '" value="' . $value['url'] . '">'
            . '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        $url = new Model($fieldName);
        $link = $url->getUrlWithPrefix($values, $this->model->getParentUrl());
        if ($link == '---') {
            // Если это страница внутри главной, то просто возвращаем поле url
            $link = $values[$fieldName];
        } else {
            // Если это не страница внутри Главной, то делаем ссылку
            $link = '<a href="' . $link . '" target="_blank">' . $link . '</a>';
        }
        return $link;
    }

    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // Если редактируется материал и ссылка не изменилась, ошибок нет
        if (!$isCreate && $this->getValue() == $this->newValue) {
            return $item;
        }

        // Если создается новый материал или изменилась ссылка при редактировании,
        // проверяем нет используется ли уже такой URL

        // Получаем SEO ссылку на создаваемый/редактируемый материал
        $url = new Model();
        $value = array('url' => htmlspecialchars($this->newValue));
        $link = $url->getUrlWithPrefix($value, $this->model->getParentUrl());

        // Подменяем REQUEST_URI новой ссылкой и запускаем роутер
        // для проверки существования страницы по этому URL
        $_SERVER['REQUEST_URI'] = $link;
        $router = new Site\Router();
        if (!$router->is404()) {
            $item['message'] = 'URL: ' . $link . ' уже используется!';
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        // В url не нужны пробелы ни спереди, ни сзади
        $value = trim(parent::pickupNewValue());
        return $value;
    }
}
