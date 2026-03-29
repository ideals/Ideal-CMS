<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

class PluginBroker
{
    private static ?PluginBroker $instance = null;

    protected $_events = [];

    public static function getInstance(): PluginBroker
    {
        if (!self::$instance instanceof \Ideal\Core\PluginBroker) {
            self::$instance = new PluginBroker();
        }

        return self::$instance;
    }

    public function makeEvent($eventName, $params)
    {
        if (count($this->_events) === 0) {
            return $params;
        }

        if (!isset($this->_events[$eventName])) {
            return $params;
        }

        foreach ($this->_events[$eventName] as $event) {
            $plugin = new $event();
            $params = $plugin->$eventName($params);
        }

        return $params;
    }

    public function registerPlugin($eventName, $pluginClassName): void
    {
        $this->_events[$eventName][] = $pluginClassName;
    }
}
