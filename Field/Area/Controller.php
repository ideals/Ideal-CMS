<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Area;

use Ideal\Field\AbstractController;

/**
 * Отображение редактирования поля в админке в виде textarea
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'annotation' => array(
 *         'label' => 'Аннотация',
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_Area'
 *     ),
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        return
            '<textarea class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '">' . htmlspecialchars($this->getValue()) . '</textarea>';
    }
}
