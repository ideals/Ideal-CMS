<?php
namespace Ideal\Core;

class PluginBroker
{

    private static $instance;

    protected $_events = array();

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new PluginBroker();
        }
        return self::$instance;
    }

    public function makeEvent($eventName, $params)
    {
        if (count($this->_events) == 0) {
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

    public function registerPlugin($eventName, $pluginClassName)
    {
        $this->_events[$eventName][] = $pluginClassName;
    }
}