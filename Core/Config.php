<?php
namespace Ideal\Core;

use Ideal\Core\Util;

class Config
{
    /** @var array Список всех подключённых к проекту структур */
    public $structures = array();
    /** @var object Необходима для реализации паттерна Singleton */
    private static $instance;
    /** @var array Содержит все конфигурационные переменные проекта */
    private $array = array();

    /**
     * Загружает все конфигурационные переменные из файлов config.php и site_data.php
     * В дальнейшем доступ к ним осуществляется через __get этого класса
     */
    public function loadSettings()
    {
        // Подключаем описание данных для БД
        $this->import(require_once($this->cmsFolder . '/config.php'));

        // Подключаем файл с переменными изменяемыми в админке
        $this->import(require_once($this->cmsFolder . '/site_data.php'));

        // Загрузка данных из конфигурационных файлов подключённых структур
        $this->loadStructures();
    }

    /**
     * Загрузка в конфиг данных из конфигурационных файлов подключённых структур
     */
    protected function loadStructures()
    {
        // Проходимся по всем конфигам подключённых структур и добавляем их в общий конфиг
        $structures = $this->structures;
        foreach($structures as $k => $structure) {
            list($module, $struct) = explode('_', $structure['structure'], 2);
            $module = ($module == 'Ideal') ? '' : $module . '/';
            $fileName = $module . 'Structure/' . $struct . '/config.php';
            $arr = require_once($fileName);
            $structures[$k] = array_merge($structure, $arr);
        }

        // Строим массив соответствия порядковых номеров структур их названиям
        $structuresNum = array();
        foreach ($structures as $num => $structure) {
            $structureName = $structure['structure'];
            if (isset($structuresNum[$structureName])) {
                Util::addError('Повторяющееся наименование структуры; ' . $structureName);
            }
            $structuresNum[$structureName] = $num;
        }

        // Проводим инъекции данных в соответствии с конфигами структур
        foreach ($structures as $structure) {
            if (!isset($structure['params']['in_structures'])) {
                // Пропускаем структуры, в которых не заданы инъекции
                continue;
            }
            foreach ($structure['params']['in_structures'] as $structureName) {
                $num = $structuresNum[$structureName];
                $structures[$num]['params']['structures'][] = $structure['structure'];
            }
        }
        $this->structures = $structures;
    }

    /**
     * Из списка подключённых структур находит стартовую по наличию заполненного параметра startName
     *
     * @return array|bool Массив стартовой структуры, или FALSE, если структуру не удалось обнаружить
     */
    public function getStartStructure()
    {
        // TODO сделать уведомление об ошибке, если нет структуры с startName
        foreach($this->structures as $structure) {
            if (isset($structure['startName']) && ('' != $structure['startName'])) {
                return $structure;
            }
        }
        return false;
    }

    /**
     * Из списка подключённых структур находит структуру с нужным кратким наименованием
     *
     * @param string $name Краткое наименование структуры, например, Ideal_Part или Ideal_News
     *
     * @return array|bool Массив структуры с указанным названием, или FALSE, если структуру не удалось обнаружить
     */
    public function getStructureByName($name)
    {
        // TODO сделать уведомление об ошибке, если такой структуры нет
        foreach($this->structures as $structure) {
            if ($structure['structure'] == $name) {
                return $structure;
            }
        }
        return false;
    }

    /**
     * Из списка подключённых структур находит структуру с нужным идентификатором ID
     *
     * @param int $structureId ID искомой структуры
     *
     * @return array|bool Массив структуры с указанным ID, или FALSE, если структуру не удалось обнаружить
     */
    public function getStructureById($structureId)
    {
        // TODO сделать уведомление об ошибке, если такой структуры нет
        foreach($this->structures as $structure) {
            if ($structure['ID'] == $structureId) {
                return $structure;
            }
        }
        return false;
    }

    /**
     * Статический метод, возвращающий находящийся в нём динамический объект
     *
     * Этот метод реализует паттерн Singleton.
     *
     * @return Config
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Config();
        }
        return self::$instance;
    }

    /**
     * Магический метод, возвращающий по запросу $config->varName переменную varName из массива $this->array
     *
     * @param string $name Название запрашиваемой переменной
     * @return string Значение запрашиваемой переменной
     */
    public function __get($name)
    {
        if (isset($this->array[$name])) {
            return $this->array[$name];
        }
        return '';
    }

    /**
     * Магический метод, по запросу $config->varName устанавливающий в $this->array переменную varName в указанное значение

     * @param string $name Название переменной
     * @param mixed $value Значение переменной
     */
    public function __set($name, $value)
    {
        $this->array[$name] = $value;
    }

    /**
     * Импортирует все значения массива $arr в массив $this->array
     *
     * @param array $arr Массив значений для импорта
     */
    protected function import($arr)
    {
        // Проверяем, не объявлены ли переменные из импортируемого массива в этом классе
        foreach ($arr as $k => $v) {
            if (isset($this->$k)) {
                $this->$k = $v;
                unset($arr[$k]);
            }
        }
        // Объединяем импортируемый массив с основным массивом переменных конфига
        $this->array = array_merge($this->array, $arr);
    }

}
