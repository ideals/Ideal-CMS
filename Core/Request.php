<?php
namespace Ideal\Core;

class Request
{
    public function __get($name)
    {
        if (isset($_REQUEST['formValues'])) {
            parse_str($_REQUEST['formValues'], $values);
            $_REQUEST = array_merge($_REQUEST, $values);
            unset($_REQUEST['formValues']);
        }

        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }

        return '';
    }

    public function __set($name, $value)
    {
        $_REQUEST[$name] = $value;
    }

    public function getQueryWithout($without)
    {
        // Убираем переменную $without стоящую внутри GET-строки
        $uri = preg_replace('/' . $without . '\=(.*)(\&|$)/iU', '', $_SERVER['REQUEST_URI']);
        // Убираем переменную $without в конце строки
        $uri = preg_replace('/' . $without . '\=(.*)(\&|$)/iU', '', $uri);
        // Убираем последний амперсанд, если остался после предыдущих операций
        $uri = preg_replace('/\&$/', '', $uri);
        // Убираем последний знак вопроса, если остался после предыдущих операций
        $uri = preg_replace('/\?$/', '', $uri);
        return $uri;
    }
}