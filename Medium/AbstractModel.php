<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Medium;

use Ideal\Core\Admin\Model;
use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Абстрактный класс, реализующий основные методы для семейства классов Medium'а
 *
 * Медиумы обеспечивают предоставление данных для Select и SelectMulti, а также их наследников.
 * А также генерируют запросы для сохранения связи многие ко многим в полях вида SelectMulti.
 *
 */
class AbstractModel
{
    /** @var string Название редактируемого поля */
    protected $fieldName;

    /** @var array Список полей в медиум-таблице, если она есть */
    protected $fields;

    /** @var Model Модель редактируемого элемента */
    protected $obj;

    /** @var array Настройки медиума из конфигурационного файла */
    protected $params;

    /** @var string Название промежуточной таблицы, которая связывает владельца и список элементов */
    protected string $table;

    /**
     * @param Model $obj
     * @param string $fieldName
     * @throws \Exception
     */
    public function __construct($obj, $fieldName)
    {
        $config = Config::getInstance();

        $this->obj = $obj;
        $this->fieldName = $fieldName;

        $parts = preg_split('/[_\\\\]+/', get_class($this));
        $this->table = strtolower($config->db['prefix'] . $parts[0] . '_' . $parts[1] . '_' . $parts[2]);
        $module = $parts[0];
        $module = ($module == 'Ideal') ? '' : $module . '/';

        $structureName = $parts[2];

        $includeFile = $module . 'Medium/' . $structureName . '/config.php';
        /** @noinspection PhpIncludeInspection */
        $structure = include($includeFile);
        if (!is_array($structure)) {
            throw new \Exception('Не удалось подключить файл: ' . $includeFile);
        }

        $this->params = $structure['params'];
        $this->fields = $structure['fields'];
    }

    /**
     * Получение списка элементов для отображения в select'е или другом поле редактирования
     *
     * @throws \Exception
     * @return array|void
     */
    public function getList()
    {
        throw new \Exception('Вызов в медиуме ' . get_class($this) . ' не переопределённого метода getList');
    }

    /**
     * Получение дополнительных sql-запросов для сохранения списка выбранных элементов для владельца
     *
     * @param mixed $newValue
     */
    public function getSqlAdd($newValue): string
    {
        $fieldNames = array_keys($this->fields);
        $ownerField = $fieldNames[0];
        $elementsField = $fieldNames[1];

        // Удаляем все существующие связи владельца и элементов
        $_sql = sprintf("DELETE FROM %s WHERE %s='{{ objectId }}';", $this->table, $ownerField);

        if (!is_array($newValue) || ($newValue === [])) {
            // Если $newValue не массив, значит ни один элемент не задан
            return $_sql;
        }

        // Добавляем связи владельца и элементов сделанные пользователем
        foreach ($newValue as $v) {
            $_sql .= sprintf("INSERT INTO %s SET %s='{{ objectId }}', %s='%s';", $this->table, $ownerField, $elementsField, $v);
        }

        return $_sql;
    }

    /**
     * Получение списка элементов выбранных в SelectMulti для этого владельца
     *
     * @return array Список выбранных элементов
     */
    public function getValues(): array
    {
        $fieldNames = array_keys($this->fields);
        $ownerField = $fieldNames[0];
        $elementsField = $fieldNames[1];
        $list = [];

        // Определяем владельца медиума
        $db = Db::getInstance();
        $owner = $this->obj->getPageData();

        // Если владельца нет (он только создаётся), то и связей нет
        if (count($owner) === 0) {
            return $list;
        }

        // Находим все медиумные связи между владельцем и выбранными элементами в SelectMulti
        $_sql = sprintf("SELECT %s FROM %s WHERE %s='%s'", $elementsField, $this->table, $ownerField, $owner['ID']);
        $arr = $db->select($_sql);

        foreach ($arr as $v) {
            $list[] = $v[$elementsField];
        }

        return $list;
    }
}
