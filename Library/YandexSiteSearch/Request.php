<?php

namespace YandexSiteSearch;

use YandexSiteSearch\Exceptions\YandexSiteSearchException;
use SimpleXMLElement;
use stdClass;

/**
 * Class Request for work with Yandex Site Search
 */
class Request
{
    /**
     * Base url to service
     */
    protected string $baseUrl = 'https://yandex.ru/search/xml';

    /**
     * API key
     */
    protected string $apiKey;

    /**
     * Идентификатор каталога
     * https://cloud.yandex.ru/docs/resource-manager/operations/folder/get-id
     */
    protected string $folderId;

    /**
     * Сайт, по которому будут искаться результаты
     */
    protected string $site;

    /**
     * Текст поискового запроса
     */
    protected string $query;

    /**
     * Фильтрация ответов (возможные варианты: none, moderate, strict)
     */
    protected string $filter = 'none';

    /**
     * Number of page
     */
    protected int $page = 0;

    /**
     * Number of results per page
     */
    protected int $perPage = 10;

    public function __construct(string $apiKey, string $searchId)
    {
        $this->apiKey = $apiKey;
        $this->folderId = $searchId;
    }

    /**
     * Set Base URL
     * @param string $baseUrl
     * @return Request
     */
    protected function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = $baseUrl;

        return $this;
    }

    /**
     * Set site
     *
     * @param string $site
     *
     * @return Request
     */
    public function setSite(string $site): self
    {
        $this->site = $site;

        return $this;
    }

    /**
     * Set query
     *
     * @param string $query
     *
     * @return Request
     */
    public function setQuery(string $query): self
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Page number
     *
     * @param int $page
     *
     * @return Request
     */
    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Set limit
     *
     * @param int $perPage
     *
     * @return Request
     */
    public function setPerPage(int $perPage): self
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Вид фильтрации
     *
     * @param string $filter
     *
     * @return Request
     */
    public function setFilter(string $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Send request
     *
     * @throws YandexSiteSearchException
     * @return Response
     */
    public function send()
    {
        if (empty($this->query)) {
            throw new YandexSiteSearchException(YandexSiteSearchException::EMPTY_QUERY);
        }

        $query = $this->query;
        if (isset($this->site)) {
            $query .= ' site:' . $this->site;
        }

        $data = [
            'apikey' => $this->apiKey,
            'folderid' => $this->folderId,
            'query' => $query,
            'page' => $this->page,
            'filter' => $this->filter,
        ];

        $url = $this->baseUrl . '?' . http_build_query($data);

        $content = file_get_contents($url);

        $simpleXML = new SimpleXMLElement($content);

        /** @var SimpleXMLElement $simpleXML */
        $simpleXML = $simpleXML->response;

        // check response error
        if (isset($simpleXML->error)) {
            $code = (int) $simpleXML->error->attributes()->code[0];
            $message = (string) $simpleXML->error;

            throw new YandexSiteSearchException($message, $code);
        }

        $response = new Response();

        // results
        $results = [];
        foreach ($simpleXML->results->grouping->group as $group) {
            $res = new stdClass();
            $res->url = (string) $group->doc->url;
            $res->domain = (string) $group->doc->domain;
            $res->title = isset($group->doc->title) ? Client::highlight($group->doc->title) : $res->url;
            $res->headline = isset($group->doc->headline) ? Client::highlight($group->doc->headline) : null;

            $passages = array();
            if (isset($group->doc->passages->passage)) {
                foreach ($group->doc->passages->passage as $passage) {
                    $passages[] = Client::highlight($passage);
                }
            }
            $res->passages = $passages;

            $res->sitelinks = isset($group->doc->snippets->sitelinks->link) ? Client::highlight(
                $group->doc->snippets->sitelinks->link
            ) : null;

            $results[] = $res;
        }
        $response->results($results);


        // total results
        $res = $simpleXML->xpath('found[attribute::priority="all"]');
        $total = (int) $res[0];
        $response->total($total);

        // total in human text
        $res = $simpleXML->xpath('found-human');
        $totalHuman = $res[0];
        $response->totalHuman($totalHuman);

        // pages
        $response->pages(floor($total / $this->perPage));

        return $response;
    }
}
