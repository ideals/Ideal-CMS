<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\RichEdit;

use Ideal\Field\AbstractController;
use Ideal\Core\Config;

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
    public function getInputText()
    {
        $config = Config::getInstance();
        $value = htmlspecialchars($this->getValue());
        $html = <<<HTML
            <textarea name="{$this->htmlName}"
                id="{$this->htmlName}">{$value}</textarea>
            <script>
                CKFinder.setupCKEditor( null, "/{$config->cmsFolder}/Ideal/Library/ckfinder/" );
                CKEDITOR.replace("{$this->htmlName}");
            </script>
HTML;
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $html = '<div id="' . $this->htmlName . '-control-group">'
            . $this->getLabelText() . '<br />' . $this->getInputText() . '</div>';
        return $html;
    }
}
