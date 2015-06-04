<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\ImageGallery;

use Ideal\Field\AbstractController;

/**
 * Поле редактирования фотогалереи
 *
 * Поле представляет из себя заголовок и кнопку выбора.
 * После выбора выстраивается список изображений с возможностью сортировки.
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'img' => array(
 *         'label' => 'Фотогалерея',
 *         'sql'   => 'mediumtext',
 *         'type'  => 'Ideal_ImageGallery'
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
        $html = <<<HTML
            <input type="hidden" id="' . $this->htmlName . '" name="' . $this->htmlName . '" value="">
            <div id="' . $this->htmlName . '-control-group">
                <div class="text-center"><strong>{$this->getLabelText()}</strong></div><br />
                <div class="text-center">
                    <span class="input-group-btn"><button class="btn">Выбрать</button></span>
                </div>
                {$this->getInputText()}
            </div>
HTML;
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        return '<div id="' . $this->htmlName . '-list" class="input-group"></div>';
    }
}
