<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

/**
 * Класс предназначен для генерации sql запросов фильтрации и сортировки
 *
 */
abstract class Filter
{

    /** @var array Массив для хранения параметров фильтрации и сортировки */
    protected $params = array();

    /** @var string Строка содержащая информацию о фильтрации в запросе */
    protected $where = '';

    /** @var string Строка содержащая информацию о сортировке в запросе */
    protected $orderBy = '';

    /**
     * Генерирует запрос для получения списка элементов
     */
    abstract public function getSql();


    /**
     * Генерирует запрос для получения количества элементов в списке
     */
    abstract public function getSqlCount();

    /**
     * Генерирует where часть запроса и сохраняет её в свойство 'where'
     */
    abstract protected function generateWhere();

    /**
     * Устанавливает значения параметров фильтрации и сортировки
     *
     * @param $params array список параметров для фильтрации
     */
    public function setParams($params)
    {
        $db = DB::getInstance();
        foreach ($params as $key => $value) {
            $params[$key] = $db->escape_string($value);
        }
        $this->params = $params;
    }

    /**
     * Возвращает значения параметров фильтрации и сортировки
     *
     * @return array параметры фильтрации и сортировки
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Генерирует order by часть запроса
     */
    protected function generateOrderBy()
    {
        $this->orderBy = '';
    }
}
