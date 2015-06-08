<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\RichEdit;

use Ideal\Core\Config;
use Ideal\Field\AbstractController;

/**
 * Текстовое поле с визуальным редактором html-кода
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'content' => array(
 *         'label' => 'Текст на странице',
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_RichEdit'
 *     ),
 */
class Controller extends AbstractController
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $html = '<div id="' . $this->htmlName . '-control-group">'
            . $this->getLabelText() . '<br />' . $this->getInputText() . '</div>';
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $config = Config::getInstance();
        $value = htmlspecialchars($this->getValue());
        $html = <<<HTML
            <textarea name="{$this->htmlName}"
                id="{$this->htmlName}">{$value}</textarea>
            <script>
                CKFinder.setupCKEditor( null, "/{$config->cmsFolder}/Ideal/Library/ckfinder/" );
                // Закрываем от авто модификации и wysiwig-редактирования содержимое тега script
                CKEDITOR.config.protectedSource.push(/<script[\\s\\S]*?script>/ig);
                // Код в блоке <div class="protectedSource"></div> не будет редактироваться во WYSIWIG,
                // но будет доступен в режиме редактирования исходного кода HTML
                CKEDITOR.config.protectedSource.push(/<div[\\s\\S]*?class="protected"[\\s\\S]*?<\\/div>/g);
                // Разрешаем использовать для всех тегов — атрибуты style и class
                CKEDITOR.config.extraAllowedContent = '*(*)[style]{*}; *(*)[class]{*}; span(*); style; *(*)[data-*]{*}';
                CKEDITOR.replace("{$this->htmlName}");
            </script>
HTML;
        return $html;
    }
}
