<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
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
    public function showEdit()
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        $input = $this->getInputText();
        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        return '<input type="hidden" id="' . $this->htmlName
        . '" name="' . $this->htmlName
        . '" value="' . $this->getValue() . '">';
    }

    /**
     * Используется для присвоения значения скрытому полю идентификатора таба, если оно пустое (при добавлении нового
     * аддона)
     */
    public function parseInputValue($isCreate)
    {
        // TODO переделать, чтобы использовать sqlAdd
        $item = parent::parseInputValue($isCreate);
        if (strpos($item['fieldName'], 'tab_ID') === false) {
            return $item;
        } else {
            if (empty($item['value'])) {
                list($tabID) = explode('_', $item['fieldName'], 2);
                list(, $tabID) = explode('-', $tabID);
                $item['sqlAdd'] = $this->getSqlAdd($tabID);
            }
            return $item;
        }
    }

    /**
     * Получение дополнительных sql-запросов для обновления идентификатора таба при создании нового аддона
     *
     * @param $tabID - идентификаор таба
     * @return string
     */
    public function getSqlAdd($tabID)
    {
        // Удаляем все существующие связи владельца и элементов
        $_sql = "UPDATE {$this->model->_table} SET {$this->name}=$tabID WHERE ID='{{ objectId }}';";
        return $_sql;
    }
}
