<?php

namespace YandexSiteSearch;

use YandexSiteSearch\Exceptions\YandexSiteSearchException;

/**
 * Class Request for work with Yandex Site Search
 */
class CatalogRequest
{
    /**
     * Base url to service
     */
    protected string $baseUrl = 'https://catalogapi.site.yandex.net/v1.0';

    /**
     * API key
     */
    protected string $apiKey;

    /**
     * Key
     */
    protected string $searchId;

    /**
     * Query
     */
    protected string $text;

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
        $this->searchId = $searchId;
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
     * Set query
     *
     * @param string $text
     *
     * @return Request
     */
    public function setText(string $text): self
    {
        $this->text = $text;

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
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage;

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
        if (empty($this->text)) {
            throw new YandexSiteSearchException(YandexSiteSearchException::EMPTY_QUERY);
        }

        $query = [
            'apikey' => $this->apiKey,
            'searchid' => $this->searchId,
            'text' => $this->text,
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];

        $url = $this->baseUrl . '?' . http_build_query($query);

        $content = file_get_contents($url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
        curl_setopt($ch, CURLOPT_POST, true);

        if (!empty($this->proxy['host'])) {
            $this->applyProxy($ch);
        }

        $data = curl_exec($ch);

        $simpleXML = new \SimpleXMLElement($data);

        /** @var \SimpleXMLElement $simpleXML */
        $simpleXML = $simpleXML->response;

        // check response error
        if (isset($simpleXML->error)) {
            $code = (int) $simpleXML->error->attributes()->code[0];
            $message = (string) $simpleXML->error;

            throw new YandexXmlException($message, $code);
        }

        $response = new Response();

        // results
        $results = array();
        foreach ($simpleXML->results->grouping->group as $group) {
            $res = new \stdClass();
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
        $response->pages(floor($total / $this->limit()));

        return $response;
    }
}
