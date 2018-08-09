<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Interaction;

interface InteractionInterface
{
    /**
     * Получение списка элементов взаимодействия определённого типа
     *
     * @param array $contactPersons Массив с идентификаторами контактных лиц
     * @return array Список взаимодействий определённого типа
     */
    public function getInteractions($contactPersons);
}
