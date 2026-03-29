<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Hidden;

use Ideal\Field\AbstractController;

/**
 * Поле, недоступное для редактирования пользователем в админке.
 *
 * Отображается в виде скрытого поля ввода <input type="hidden" />
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'date_create' => array(
 *         'label' => 'ID родительских структур',
 *         'sql'   => 'char(15)',
 *         'type'  => 'Ideal_Hidden'
 *     ),
 */
class Controller extends AbstractController
{
    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function showEdit(): string
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        return $this->getInputText();
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText(): string
    {
        return '<input type="hidden" id="' . $this->htmlName
        . '" name="' . $this->htmlName
        . '" value="' . $this->getValue() . '">';
    }
}
