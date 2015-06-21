<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Referer;

use Ideal\Field\AbstractController;

/**
 * Поле для работы с источником посещений
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'name' => array(
 *         'label' => 'Источник посещения',
 *         'sql'   => 'varchar(255) not null',
 *         'type'  => 'Ideal_Referer'
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
        $value = htmlspecialchars(nl2br($this->getValue()));
        return '<div class="well">' . $value . '</div>';
    }
}
