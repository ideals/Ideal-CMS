<?php
namespace Ideal\Addon;

use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Абстрактный класс, реализующий основные методы для семейства классов Addon
 *
 * Аддоны обеспечивают прикрепление к структуре дополнительного содержимого различных типов.
 *
 */
class AbstractModel extends \Ideal\Core\Admin\Model
{

    /** @var array массив данных соседних аддонов */
    protected $neighborAddonsData = array();

    public function setPageDataByPrevStructure($prevStructure)
    {
        $db = Db::getInstance();

        // Получаем идентификатор таба из группы
        list(, $tabID) = explode('-', $this->fieldsGroup, 2);
        $_sql = "SELECT * FROM {$this->_table} WHERE prev_structure=:ps AND tab_ID=:tid";
        $pageData = $db->select($_sql, array('ps' => $prevStructure, 'tid' => $tabID));
        if (isset($pageData[0]['ID'])) {
            // TODO сделать обработку ошибки, когда по prevStructure ничего не нашлось
            $this->setPageData($pageData[0]);
        }
    }

    public function delete()
    {
        $db = Db::getInstance();
        $db->delete($this->_table)->where('ID=:id', array('id' => $this->pageData['ID']));
        $db->exec();
    }

    /**
     * Получает данные из полей соседних аддонов
     * @param string $field обозначение поля в аддоне
     * @return string Данные из поля определённого аддона
     */
    public function getNeighborAddonsData($field)
    {
        $data = '';

        // Разбиваем обозначение поля на составляющие (название аддона, название поля)
        $parts = explode('_', $field);
        $addonName = implode('_', array($parts[1], $parts[2]));
        $fieldName = $parts[3];

        // Проверяем есть ли данные из соседних аддонов
        // Если данных нет, то получаем
        if (!$this->neighborAddonsData) {
            $pageData = $this->parentModel->getPageData();
            $config = Config::getInstance();
            $db = DB::getInstance();

            // Проверяем есть ли подключенные аддоны
            if (!empty($pageData['addon'])) {
                $addons = json_decode($pageData['addon']);
                $structure = $config->getStructureByName($pageData['structure']);
                $prevStructure = $structure['ID'] . '-' . $pageData['ID'];
                $addonDataQueries = array();

                // Формируем запросы для получения информации из различных аддонов
                foreach ($addons as $key => $value) {
                    if (!isset($addonDataQueries[$value[1]])) {
                        $addonTable = $config->getTableByName($value[1], 'Addon');
                        $addonDataQueries[$value[1]] = "SELECT * FROM {$addonTable} WHERE prev_structure = '{$prevStructure}'";
                    }
                }

                // Получаем данные из соседних аддонов
                foreach ($addonDataQueries as $key => $value) {
                    $tempData = $db->select($value);
                    if ($tempData) {
                        $this->neighborAddonsData[$key] = $tempData;
                    }
                }
            }
        }

        // Если данные из интересующего аддона получены, то ищем в них нужное поле
        if (isset($this->neighborAddonsData[$addonName])) {
            foreach ($this->neighborAddonsData[$addonName] as $addonContent) {
                if (isset($addonContent[$fieldName])) {
                    $data .= $addonContent[$fieldName];
                }
            }
        }
        return $data;
    }

    /**
     * Получает данные из полей родительской структуры аддона
     * @param string $field назание поля
     * @return string Данные из определённого поля родительской структуры
     */
    public function getFieldData($field)
    {
        $pageData = $this->parentModel->getPageData();
        return isset($pageData[$field]) ? $pageData[$field] : '';
    }
}
