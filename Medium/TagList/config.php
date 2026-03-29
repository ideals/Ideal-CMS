<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

return [
    'params' => [
        'has_table' => true,
    ],
    'fields' => [
        'part_id' => [
            'label' => 'Идентификатор страницы',
            'sql'   => 'int(11)',
        ],
        'tag_id' => [
            'label' => 'Идентификатор тега',
            'sql'   => 'int(11)',
        ],
        'structure_id' => [
            'label' => 'Структура, элементу которой присвоен тег',
            'sql'   => 'char(15)',
        ],
    ],
];
