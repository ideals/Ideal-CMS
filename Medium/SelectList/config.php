<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
return array(
    'params' => array(
        'create_table' => true
    ),
    'fields' => array(
        'id_parent' => array(
            'label' => 'Идентификатор родителя',
            'sql' => 'int(11)',
            'type' => 'Ideal_Hidden',
            'from' => 'table_name',
            'fieldID' => 'ID',
            'fieldName' => 'name'
        ),
        'id_children' => array(
            'label' => 'Идентификатор потомка',
            'sql' => 'int(11)',
            'type' => 'Ideal_Hidden',
            'from' => 'table_name',
            'fieldID' => 'ID',
            'fieldName' => 'name'
        )
    )
);
