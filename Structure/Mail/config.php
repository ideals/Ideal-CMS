<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Почта
return array(
    'params' => array(
        'in_structures' => array('Ideal_DataList'), // в каких структурах можно создавать эту структуру
        'elements_cms' => 10, // количество элементов в списке в CMS
        'elements_site' => 15, // количество элементов в списке на сайте
        'field_name' => '', // поле для входа в список потомков
        'field_sort' => 'date_received DESC', // поле, по которому проводится сортировка в CMS
        'field_list' => array('ID', 'subject', 'date_received')
    ),
    'fields' => array(
        'ID' => array(
            'label' => 'Идентификатор',
            'sql' => 'int(4) unsigned not null auto_increment primary key',
            'type' => 'Ideal_Hidden'
        ),
        'prev_structure' => array(
            'label' => 'ID родительских структур',
            'sql' => 'char(15)',
            'type' => 'Ideal_Hidden'
        ),
        'UID' => array(
            'label' => 'Уникальный идентификатор сообщения на почте',
            'sql' => 'char(15)',
            'type' => 'Ideal_Text'
        ),
        'subject' => array(
            'label' => 'Тема письма',
            'sql' => 'text ',
            'type' => 'Ideal_Text'
        ),
        'from' => array(
            'label' => 'От кого',
            'sql' => 'text ',
            'type' => 'Ideal_Text'
        ),
        'date_received' => array(
            'label' => 'Дата получения',
            'sql' => 'int(11) not null',
            'type' => 'Ideal_DateSet'
        ),
        'body' => array(
            'label' => 'Тело письма',
            'sql' => 'varchar(255)',
            'type' => 'Ideal_RichEdit'
        ),
        'attachment' => array(
            'label' => 'Список прикрепленных файлов',
            'sql' => 'text ',
            'type' => 'Ideal_Text'
        ),
    ),
);
