<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

use Nyholm\Dsn\DsnParser;

/**
 * Класс конфигурации, в котором хранятся все конфигурационные данные CMS
 *
 * @property array $db Массив с настройками подключения к БД
 * @property array $cache Массив с настройками кэширования
 * @property string $cmsFolder Название папки с CMS
 * @property array $yandex Массив с настройками подключения к сервисам Яндекса
 * @property string $domain Доменная часть адреса сайта на котором установлена CMS
 * @property array $cms Массив настроек cms
 * @property string $urlSuffix Стандартный суффикс url для страниц сайта (обычно .html)
 * @property array $smtp Массив с настройками SMTP
 */
class Config
{
    /** @var object Необходима для реализации паттерна Singleton */
    private static $instance;

    /** @var array Список всех подключённых к проекту структур */
    public array $structures = [];

    /** @var array Содержит все конфигурационные переменные проекта */
    private array $array = [];

    private string $protocol;

    /**
     * Магический метод, возвращающий по запросу $config->varName переменную varName из массива $this->array
     *
     * @param string $name Название запрашиваемой переменной
     * @return string Значение запрашиваемой переменной
     */
    public function __get(string $name)
    {
        return $this->array[$name] ?? '';
    }

    /**
     * Магический метод, по $config->varName устанавливающий в $this->array переменную varName в указанное значение
     *
     * @param string $name Название переменной
     * @param mixed $value Значение переменной
     */
    public function __set(string $name, $value)
    {
        $this->array[$name] = $value;
    }

    public function __isset(string $name)
    {
        return isset($this->array[$name]);
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
     * Из списка подключённых структур находит стартовую по наличию заполненного параметра startName
     *
     * @return array|bool Массив стартовой структуры, или FALSE, если структуру не удалось обнаружить
     */
    public function getStartStructure()
    {
        // TODO сделать уведомление об ошибке, если нет структуры с startName
        foreach ($this->structures as $structure) {
            if (isset($structure['startName']) && ($structure['startName'] != '')) {
                return $structure;
            }
        }

        return false;
    }

    /**
     * Из списка подключённых структур находит структуру на основании имени её класса
     *
     * @param string $className
     *
     * @return array|bool Массив структуры с указанным ID, или FALSE, если структуру не удалось обнаружить
     */
    public function getStructureByClass($className)
    {
        $className = trim($className, '\\');
        $className = explode('\\', $className);
        $className = $className[0] . '_' . $className[2];

        return $this->getStructureByName($className);
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
        // TODO что делать, если с таким именем определено несколько структур
        // TODO сделать уведомление об ошибке, если такой структуры нет
        foreach ($this->structures as $structure) {
            if ($structure['structure'] == $name) {
                return $structure;
            }
        }

        return false;
    }

    /**
     * Из списка подключённых структур находит структуру на основе prev_structure
     *
     * @param string $prevStructure
     *
     * @return array|bool Массив структуры с указанным ID, или FALSE, если структуру не удалось обнаружить
     */
    public function getStructureByPrev($prevStructure)
    {
        $prev = explode('-', $prevStructure);
        $structureId = $prev[0] == 0 ? $prev[1] : $prev[0];

        return $this->getStructureById($structureId);
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
        foreach ($this->structures as $structure) {
            if ($structure['ID'] == $structureId) {
                return $structure;
            }
        }

        return false;
    }

    /**
     * Получение имени таблицы на основе названия структуры или аддона
     *
     * @param string $name Название структуры или аддона
     * @param string $type Тип класса (Structure или Addon)
     * @return string Название таблицы
     * @throws \Exception
     */
    public function getTableByName($name, $type = 'Structure'): string
    {
        $name = strtolower($name);
        $name = explode('_', $name);
        if (count($name) !== 2) {
            throw new \Exception('Передано неправильное значение названия структуры: ' . $name);
        }

        return $this->db['prefix'] . $name[0] . '_' . strtolower($type) . '_' . $name[1];
    }

    /**
     * Загружает все конфигурационные переменные из файлов config.php и site_data.php
     * В дальнейшем доступ к ним осуществляется через __get этого класса
     */
    public function loadSettings(): void
    {
        // Подключаем описание данных для БД
        /** @noinspection PhpIncludeInspection */
        $this->import(include ($this->cmsFolder . '/config.php'));

        // Подключаем файл с переменными изменяемыми в админке
        /** @noinspection PhpIncludeInspection */
        $this->import(include ($this->cmsFolder . '/site_data.php'));

        // Загрузка данных из конфигурационных файлов подключённых структур
        $this->loadStructures();

        if (isset($_ENV['MAILER_DSN'])) {
            $dsn = DsnParser::parse($_ENV['MAILER_DSN']);
            $this->array['smtp']['isActive'] = '1';
            $this->array['smtp']['server'] = $dsn->getHost();
            $this->array['smtp']['port'] = $dsn->getPort();
            $this->array['smtp']['user'] = $dsn->getUser();
            $this->array['smtp']['password'] = $dsn->getPassword();
        }
    }

    /**
     * Получаем протокол, по которому работает сайт
     *
     * @return string Протокол сайта (http:// или https://)
     */
    public function getProtocol(): string
    {
        if (isset($this->protocol) && ($this->protocol !== '' && $this->protocol !== '0')) {
            return $this->protocol;
        }

        if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) {
            $this->protocol = 'https://';
            return $this->protocol;
        }

        if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 80) {
            $this->protocol = 'http://';
            return $this->protocol;
        }

        // В консольных запросах единственный способ ориентировки - по настройкам карты сайта
        $config = Config::getInstance();
        $sitemapFile = $config->cmsFolder . '/site_map.php';
        if (file_exists($sitemapFile)) {
            $siteMap = include($sitemapFile);
            if (!empty($siteMap['website'])) {
                $isHttps = stripos($siteMap['website'], 'https');
                if ($isHttps === 0) {
                    $this->protocol = 'https://';
                    return $this->protocol;
                }
            }
        }

        $this->protocol = 'http://';
        return $this->protocol;
    }

    /**
     * Импортирует все значения массива $arr в массив $this->array
     *
     * @param array $arr Массив значений для импорта
     */
    protected function import(array $arr)
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

    /**
     * Загрузка в конфиг данных из конфигурационных файлов подключённых структур
     */
    protected function loadStructures()
    {
        // Проходимся по всем конфигам подключённых структур и добавляем их в общий конфиг
        $structures = $this->structures;
        foreach ($structures as $k => $structureName) {
            [$module, $structure] = explode('_', $structureName['structure'], 2);
            $module = ($module === 'Ideal') ? '' : $module . '/';
            $fileName = $module . 'Structure/' . $structure . '/config.php';
            /** @noinspection PhpIncludeInspection */
            $arr = require_once($fileName);
            if (is_array($arr)) {
                $structures[$k] = array_merge($structureName, $arr);
            }
        }

        // Строим массив соответствия порядковых номеров структур их названиям
        $structuresNum = [];
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
}
