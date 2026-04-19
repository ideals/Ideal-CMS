<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Area;

use Ideal\Field\AbstractController;

/**
 * Отображение редактирования поля в админке в виде textarea
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'annotation' => [
 *         'label' => 'Аннотация',
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_Area',
 *         'rows'  => 3, // Количество строк в поле textarea
 *     ],
 */
class Controller extends AbstractController
{
    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText(): string
    {
        $rows = $this->field['rows'] ?? 3;

        return
            '<textarea class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName . '" rows=" ' . $rows
            . '">' . htmlspecialchars($this->getValue()) . '</textarea>';
    }
}
