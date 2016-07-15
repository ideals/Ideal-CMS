<?php

namespace YandexXML;

/**
 * Class YandexXml for work with Yandex.XML
 *
 * @author   Anton Shevchuk <AntonShevchuk@gmail.com>
 * @author   Mihail Bubnov <bubnov.mihail@gmail.com>
 * @link     http://anton.shevchuk.name
 * @link     http://yandex.hohli.com
 *
 * @package  YandexXml
 */
class Response
{
    /**
     * Response in array
     *
     * @var array
     */
    protected $results = array();

    /**
     * Total results
     *
     * @var integer
     */
    protected $total = null;

    /**
     * Total results in human form
     *
     * @var string
     */
    protected $totalHuman = null;

    /**
     * Number of total pages
     *
     * @var integer
     */
    protected $pages = 0;

    /**
     * Set/Get Results
     *
     * @param  array $results
     * @return Response|array
     */
    public function results($results = null)
    {
        if (is_null($results)) {
            return $this->results;
        } else {
            return $this->setResults($results);
        }
    }

    /**
     * Set associated array of groups
     *
     * @param  array $results
     * @return Response
     */
    protected function setResults($results)
    {
        $this->results = $results;
        return $this;
    }

    /**
     * Set/Get total results
     *
     * @param  integer $total
     * @return Response|integer
     */
    public function total($total = null)
    {
        if (is_null($total)) {
            return $this->total;
        } else {
            return $this->setTotal($total);
        }
    }

    /**
     * Set total results
     *
     * @param  integer $total
     * @return Response
     */
    protected function setTotal($total)
    {
        $this->total = (int) $total;
        return $this;
    }


    /**
     * Set/Get total results in human form
     *
     * @param  string $total
     * @return Response|string
     */
    public function totalHuman($total = null)
    {
        if (is_null($total)) {
            return $this->totalHuman;
        } else {
            return $this->setTotalHuman($total);
        }
    }

    /**
     * Set total results in human form
     *
     * @param  string $total
     * @return Response
     */
    protected function setTotalHuman($total)
    {
        $this->totalHuman = $total;
        return $this->totalHuman;
    }

    /**
     * Set/Get total pages
     *
     * @param integer $pages
     * @return Response|integer
     */
    public function pages($pages = null)
    {
        if (is_null($pages)) {
            return $this->pages;
        } else {
            return $this->setPages($pages);
        }
    }

    /**
     * Set total pages
     *
     * @param  integer $pages
     * @return Response
     */
    protected function setPages($pages)
    {
        $this->pages = $pages;
        return $this;
    }
}
