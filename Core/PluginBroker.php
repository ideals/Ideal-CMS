<?php
namespace Ideal\Core;

class PluginBroker
{
    protected $_events = array();
    private static $instance;


    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new PluginBroker();
        }
        return self::$instance;
    }


    public function registerPlugin($eventName, $pluginClassName)
    {
        $this->_events[$eventName][] = $pluginClassName;
    }


    public function makeEvent($eventName, $params)
    {
        if (count($this->_events) == 0) return $params;

        if (!isset($this->_events[$eventName])) return $params;

        foreach ($this->_events[$eventName] as $event) {
            $plugin = new $event();
            $params = $plugin->$eventName($params);
        }
        return $params;
    }
}