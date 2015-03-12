<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Template;

use Ideal\Field\Select;

/**
 * Специальное поле, предоставляющее возможность выбрать шаблон для отображения структуры
 *
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'template' => array(
 *         'label' => 'Шаблон отображения',
 *         'sql' => "varchar(255) default 'index.twig'",
 *         'type' => 'Ideal_Template',
 *         'medium' => '\\Ideal\\Medium\\TemplateList\\Model',
 *         'default'   => 'index.twig',
 *
 * В поле medium указывается класс, отвечающий за предоставление списка элементов для select.
 */
class Controller extends Select\Controller
{

    /** @inheritdoc */
    protected static $instance;
}
