<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

// Взаимодействие
return array(
    'params' => array(
        'field_sort' => 'date_create DESC',
        'field_list' => array('interaction_type', 'date_create', 'contact_person'),
        'elements_cms' => 10
    ),
    'fields' => array(
        'interaction_type' => array(
            'label' => 'Тип взаимодействия',
            'type' => 'Ideal_Text'
        ),
        'date_create' => array(
            'label' => 'Дата создания',
            'type' => 'Ideal_DateSet'
        ),
        'contact_person' => array(
            'label' => 'Контактное лицо',
            'type' => 'Ideal_ContactPerson'
        ),
    )
);
