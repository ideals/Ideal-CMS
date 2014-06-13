<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

/**
 * Обёртка над Memcache, добавляющая теггирование
 *
 * Пример использования:
 *     $cache = Memcache::getInstance();
 *     $cache->set('key', 'value', $ttl = 0, 'tag);
 *     $cache->get('key');
 *     $cache->deleteByTag('tag');
 */
class Memcache extends \Memcache
{
    const FALSE_VALUE = '-s95VSn.zMbP(ph1-S6M]Q.c$e<9wV-h';

    /** @var array Массив для хранения подключений к разным серверам кэширования */
    private static $connectedServers;

    /**
     * Получение singleton-объекта Memcache
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
     * Добавляет значение $value по ключу $key в случае, если значение с $key не было установлено ранее
     *
     * @param string       $key      Ключ для записи $value
     * @param mixed        $value    Значение, помещаемое в кэш
     * @param bool         $ttl      Время жизни значения в кэше
     * @param string|array $tagsKeys Строка или массив с тегами для ключа $key
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function addWithTags($key, $value, $ttl = false, $tagsKeys = 'default')
    {
        $value = $this->createTagsContainer($value, $tagsKeys);

        if (false === $value) {
            $value = self::FALSE_VALUE;
        }

        return parent::add($key, $value, false, (int)$ttl);
    }

    /**
     * Устанавливает значение $value по ключу $key в кэше
     *
     * @param string       $key      Ключ для записи $value
     * @param mixed        $value    Значение, помещаемое в кэш
     * @param bool         $ttl      Время жизни значения в кэше
     * @param string|array $tagsKeys Строка или массив с тегами для ключа $key
     * @return bool Возвращает true при успешном выполнении и false в случае ошибки
     */
    public function setWithTags($key, $value, $ttl = false, $tagsKeys = 'default')
    {
        $value = $this->createTagsContainer($value, $tagsKeys);

        if (false === $value) {
            $value = self::FALSE_VALUE;
        }

        return parent::set($key, $value, false, (int)$ttl);
    }

    /**
     * Получает значение по ключу $key
     *
     * @param string $key Ключ кэширования
     * @return mixed
     */
    public function getWithTags($key)
    {
        $value = parent::get($key);

        if (false === $value) {
            $value = null;
        }

        if (self::FALSE_VALUE === $value) {
            $value = false;
        }

        if (!$value) {
            return $value;
        }

        if (is_array($key)) {
            foreach ($key as $singleKey) {
                if (!isset($value[$singleKey])) {
                    $value[$singleKey] = null;
                }
            }
        }

        return $this->getFromTagsContainer($key, $value);
    }

    /**
     * Безопасное увеличение значения в memcache
     *
     * Если значения по ключу $key не было, то оно будет создано
     *
     * @param      $key
     * @param int  $value
     * @param bool $ttl
     */
    public function safeIncrement($key, $value = 1, $ttl = false)
    {
        if ($result = parent::increment($key, $value)) {
            return $result;
        }

        parent::add($key, 0, $ttl);

        return parent::increment($key, $value);
    }

    /**
     * Безопасное уменьшение значения в memcache.
     *
     * Если значения по ключу $key не было, то оно будет создано
     *
     * @param      $key
     * @param int  $value
     * @param bool $ttl
     */
    public function safeDecrement($key, $value = 1, $ttl = false)
    {
        if ($result = parent::decrement($key, $value)) {
            return $result;
        }

        parent::add($key, 0, $ttl);

        return parent::decrement($key, $value);
    }

    /**
     * Удаление значений в кеше по тегу или группе тегов
     *
     * @param $tag string|array Строка или массив тегов
     */
    public function deleteByTag($tag)
    {
        // Обновляем в кэше версию для тега

        $this->safeIncrement($tag);
    }

    /**
     * Подготовка контейнера с тегами для кеширования
     *
     * Структура контейнера:
     *     $container = array(
     *         'tags' => array(
     *             'tag_1' => 'versionOfTag1',
     *             'tag_2' => 'versionOfTag2',
     *         ),
     *         'value' => 'value',
     *     );
     *
     * @param $value mixed Кэшируемое значение
     * @param $tags  string|array Строка или массив тегов
     * @return array Контейнер для помещения в кэш
     */
    private function createTagsContainer($value, $tags)
    {
        if (!is_array($tags)) {
            $tags = array($tags);
        }

        $tagsValues = (array)parent::get($tags);

        foreach ($tags as $tagKey) {
            if (!isset($tagsValues[$tagKey]) || is_null($tagsValues[$tagKey])) {
                $tagsValues[$tagKey] = 0;
                parent::add($tagKey, 0);
            }
        }

        return array(
            'tags' => $tagsValues,
            'value' => $value
        );
    }

    /**
     * Получение значения из контейнера с тегами
     *
     * @param $key       string Ключ кэше
     * @param $container array Контейнер с тегами и значением из кэша
     * @return mixed Значение по ключу $key или null
     */
    private function getFromTagsContainer($key, $container)
    {
        if ($this->isTagsValid($container['tags'])) {
            return $container['value'];
        } else {
            $this->delete($key);

            return null;
        }
    }

    /**
     * Проверка валидности тегов контейнера
     *
     * @param $tags
     * @return bool
     */
    private function isTagsValid($tags)
    {
        // Версии тегов из кэша сравниваются с версиями, полученными из контейнера

        $tagsVersions = (array)parent::get(array_keys($tags));

        foreach ($tagsVersions as $tagKey => $tagVersion) {
            if (is_null($tagVersion) || $tags[$tagKey] != $tagVersion) {
                return false;
            }
        }

        return true;
    }
}
