<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

/**
 * Прокси класс для класса обёртки MemcacheWrapper
 *
 * Пример использования:
 *     $cache = Memcache::getInstance();
 *     $cache->set('key', 'value', $ttl = 0, 'tag);
 *     $cache->get('key');
 *     $cache->deleteByTag('tag');
 *
 * @mixin MemcacheWrapper
 */
class Memcache
{
    /** @var MemcacheWrapper|null Экземпляр класса MemcacheWrapper или null если не доступен класс Memcache */
    private $memcacheWrapper = null;

    /** @var array Массив для хранения подключений к разным серверам кэширования */
    private static $connectedServers;

    /**
     * При создании экземпляра данного класса экземпляр класса MemcacheWrapper помещается в свойство $memcacheWrapper, если класс \Memcache доступен.
     */
    public function __construct()
    {
        if (class_exists('Memcache')) {
            $this->memcacheWrapper = new MemcacheWrapper();
        }
    }

    /**
     * При обращении к методам не реализованным в данном классе происходит поиск этого метода в классе \Memcache.
     * Если нужный метод найден, то он вызывается.
     *
     * @param string $name Имя вызываемого метода
     * @param array $arguments Массив аргументов, передаваемый методу
     * @return bool|mixed Результат выполнения метода из класса \Memcache или false в случае если такой метод не реализован или класс \Memcache не доступен.
     */
    public function __call($name, $arguments)
    {
        if ($this->memcacheWrapper !== null && method_exists($this->memcacheWrapper, $name)) {
            return call_user_func_array(array($this->memcacheWrapper, $name), $arguments);
        } else {
            return false;
        }
    }

    /**
     * Получение singleton-объекта MemcacheWrapper
     *
     * Если переменная $params не задана, то данные для подключения берутся из конфига CMS
     * В массива $params должны быть следующие элементы: host, port
     *
     * @param array $params Параметры подключения
     * @return Memcache
     */
    public static function getInstance($params = null)
    {
        if (!$params) {
            $params = Config::getInstance()->memcache;
        }

        if (!is_array($params)) {
            $params = array(
                'host' => 'localhost',
                'port' => 11211
            );
        }

        $serverId = "memcache://{$params['host']}/{$params['port']}";

        if (!self::$connectedServers[$serverId]) {
            $server = new Memcache();

            if (!$server->connect($params['host'], $params['port'])) {
                Util::addError("Can't connect to memcache");
            }

            self::$connectedServers[$serverId] = $server;
        }

        return self::$connectedServers[$serverId];
    }

    /**
     * @param string $host Хост сервера
     * @param int $port Порт сервера
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function connect($host, $port)
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->connect($host, $port);
        } else {
            return false;
        }
    }

    /**
     * Добавляет значение $value по ключу $key в случае, если значение с $key не было установлено ранее
     *
     * @param string $key Ключ для записи $value
     * @param mixed $value Значение, помещаемое в кэш
     * @param bool $ttl Время жизни значения в кэше
     * @param string|array $tagsKeys Строка или массив с тегами для ключа $key
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function addWithTags($key, $value, $ttl = false, $tagsKeys = 'default')
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->addWithTags($key, $value, $ttl, $tagsKeys);
        } else {
            return false;
        }
    }

    /**
     * Удаление значений в кеше по тегу или группе тегов
     *
     * @param $tag string|array Строка или массив тегов
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function deleteByTag($tag)
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->deleteByTag($tag);
        } else {
            return false;
        }
    }

    /**
     * Безопасное увеличение значения в memcache
     *
     * Если значения по ключу $key не было, то оно будет создано
     *
     * @param $key
     * @param int $value
     * @param bool $ttl
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function safeIncrement($key, $value = 1, $ttl = false)
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->safeIncrement($key, $value, $ttl);
        } else {
            return false;
        }
    }

    /**
     * Получает значение по ключу $key
     *
     * @param string $key Ключ кэширования
     * @return mixed
     */
    public function getWithTags($key)
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->getWithTags($key);
        } else {
            return false;
        }
    }

    /**
     * Безопасное уменьшение значения в memcache.
     *
     * Если значения по ключу $key не было, то оно будет создано
     *
     * @param      $key
     * @param int $value
     * @param bool $ttl
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function safeDecrement($key, $value = 1, $ttl = false)
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->safeDecrement($key, $value, $ttl);
        } else {
            return false;
        }
    }

    /**
     * Устанавливает значение $value по ключу $key в кэше
     *
     * @param string $key Ключ для записи $value
     * @param mixed $value Значение, помещаемое в кэш
     * @param bool $ttl Время жизни значения в кэше
     * @param string|array $tagsKeys Строка или массив с тегами для ключа $key
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function setWithTags($key, $value, $ttl = false, $tagsKeys = 'default')
    {
        if ($this->memcacheWrapper !== null) {
            return $this->memcacheWrapper->setWithTags($key, $value, $ttl, $tagsKeys);
        } else {
            return false;
        }
    }
}
