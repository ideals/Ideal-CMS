<?php

namespace YandexXML;

use YandexXML\Exceptions\YandexXmlException;

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
class Request
{
    /**
     * Base url to service
     */
    protected $baseUrl = 'https://yandex.ru/search/xml';

    /**
     * User
     *
     * @var string
     */
    protected $user;

    /**
     * Key
     *
     * @var string
     */
    protected $key;

    /**
     * Query
     *
     * @var string
     */
    protected $query;

    /**
     * Request
     *
     * @var string
     */
    protected $request;

    /**
     * Host
     *
     * @var string
     */
    protected $host;

    /**
     * Site
     *
     * @var string
     */
    protected $site;

    /**
     * Domain
     *
     * @var string
     */
    protected $domain;

    /**
     * Catalog ID
     *
     * @see http://search.yaca.yandex.ru/cat.c2n
     * @var integer
     */
    protected $cat;

    /**
     * Geo ID
     *
     * @see http://search.yaca.yandex.ru/geo.c2n
     * @var integer
     */
    protected $geo;

    /**
     * Theme name
     *
     * @see http://help.yandex.ru/site/?id=1111797
     * @var integer
     */
    protected $theme;

    /**
     * lr
     *
     * @var integer
     */
    protected $lr;

    /**
     * Localization
     *  - ru - russian
     *  - uk - ukrainian
     *  - be - belarusian
     *  - kk - kazakh
     *  - tr - turkish
     *  - en - english
     *
     * @var string
     */
    const L10N_RUSSIAN = 'ru';
    const L10N_UKRAINIAN = 'uk';
    const L10N_BELARUSIAN = 'be';
    const L10N_KAZAKH = 'kk';
    const L10N_TURKISH = 'tr';
    const L10N_ENGLISH = 'en';

    protected $l10n;

    /**
     * Content filter
     *  - strict
     *  - moderate
     *  - none
     * @var string
     */
    const FILTER_STRICT = 'strict';
    const FILTER_MODERATE = 'moderate';
    const FILTER_NONE = 'none';

    protected $filter;

    /**
     * Number of page
     *
     * @var integer
     */
    protected $page = 0;

    /**
     * Number of results per page
     *
     * @var integer
     */
    protected $limit = 10;

    /**
     * Sort By   'rlv' || 'tm'
     *
     * @see https://tech.yandex.ru/xml/doc/dg/concepts/post-request-docpage/
     * @var string
     */
    const SORT_RLV = 'rlv'; // relevance
    const SORT_TM = 'tm';  // time modification

    protected $sortBy = 'rlv';

    /**
     * Group By  '' || 'd'
     *
     * @see https://tech.yandex.ru/xml/doc/dg/concepts/post-request-docpage/
     * @var string
     */
    const GROUP_DEFAULT = '';
    const GROUP_SITE = 'd'; // group by site

    protected $groupBy = '';

    /**
     * Group mode   'flat' || 'deep' || 'wide'
     *
     * @var string
     */
    const GROUP_MODE_FLAT = 'flat';
    const GROUP_MODE_DEEP = 'deep';
    const GROUP_MODE_WIDE = 'wide';

    protected $groupByMode = 'flat';

    /**
     * Options of search
     *
     * @var array
     */
    protected $options = array(
        'maxpassages' => 2,    // from 2 to 5
        'max-title-length' => 160, //
        'max-headline-length' => 160, //
        'max-passage-length' => 160, //
        'max-text-length' => 640, //
    );

    /**
     * Proxy params
     * Default - no proxy
     * @var array
     */
    protected $proxy = array(
        'host' => '',
        'port' => 0,
        'user' => '',
        'pass' => ''
    );

    /**
     * __construct
     *
     * @param  string $user
     * @param  string $key
     */
    public function __construct($user, $key)
    {
        $this->user = $user;
        $this->key = $key;
    }

    /**
     * Set Base URL
     * @param String $baseUrl
     * @return Request
     */
    public function baseUrl($baseUrl = null)
    {
        if (is_null($baseUrl)) {
            return $this->baseUrl;
        } else {
            return $this->setBaseUrl($baseUrl);
        }
    }

    /**
     * Set Base URL
     * @param String $baseUrl
     * @return Request
     */
    protected function setBaseUrl($baseUrl)
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * Query string
     *
     * @param  string $query
     * @return Request|string
     */
    public function query($query = null)
    {
        if (is_null($query)) {
            return $this->query;
        } else {
            return $this->setQuery($query);
        }
    }

    /**
     * Set query
     *
     * @param  string $query
     * @return Request
     */
    protected function setQuery($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * Page
     *
     * @param  integer $page
     * @return Request|integer
     */
    public function page($page = null)
    {
        if (is_null($page)) {
            return $this->page;
        } else {
            return $this->setPage($page);
        }
    }

    /**
     * Page number
     *
     * @param  integer $page
     * @return Request
     */
    protected function setPage($page)
    {
        $this->page = (int) $page;
        return $this;
    }

    /**
     * Limit
     *
     * @param  integer $limit
     * @return Request|integer
     */
    public function limit($limit = null)
    {
        if (is_null($limit)) {
            return $this->limit;
        } else {
            return $this->setLimit($limit);
        }
    }

    /**
     * Set limit
     *
     * @param  integer $limit
     * @return Request
     */
    protected function setLimit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Host
     *
     * @param  string $host
     * @return Request|string
     */
    public function host($host = null)
    {
        if (is_null($host)) {
            return $this->host;
        } else {
            return $this->setHost($host);
        }
    }

    /**
     * Set host
     *
     * @param  string $host
     * @return Request
     */
    protected function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * Site
     *
     * @param  string $site
     * @return Request|string
     */
    public function site($site = null)
    {
        if (is_null($site)) {
            return $this->site;
        } else {
            return $this->setSite($site);
        }
    }

    /**
     * Set site
     *
     * @param  string $site
     * @return Request
     */
    protected function setSite($site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * Domain
     *
     * @param  string $domain
     * @return Request|string
     */
    public function domain($domain = null)
    {
        if (is_null($domain)) {
            return $this->domain;
        } else {
            return $this->setDomain($domain);
        }
    }

    /**
     * Set domain
     *
     * @param  string $domain
     * @return Request
     */
    protected function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Cat
     *
     * @param  integer $cat
     * @return Request|integer
     */
    public function cat($cat = null)
    {
        if (is_null($cat)) {
            return $this->cat;
        } else {
            return $this->setCat($cat);
        }
    }

    /**
     * Set cat
     *
     * @param  integer $cat
     * @return Request
     */
    protected function setCat($cat)
    {
        $this->cat = (int) $cat;
        return $this;
    }

    /**
     * Geo
     *
     * @param  integer $geo
     * @return Request|integer
     */
    public function geo($geo = null)
    {
        if (is_null($geo)) {
            return $this->geo;
        } else {
            return $this->setGeo($geo);
        }
    }

    /**
     * Set geo
     *
     * @param  integer $geo
     * @return Request
     */
    protected function setGeo($geo)
    {
        $this->geo = (int) $geo;
        return $this;
    }

    /**
     * Theme
     *
     * @param  string $theme
     * @return Request
     */
    public function theme($theme = null)
    {
        if (is_null($theme)) {
            return $this->theme;
        } else {
            return $this->setTheme($theme);
        }
    }

    /**
     * Set theme
     *
     * @param  integer $theme
     * @return Request
     */
    protected function setTheme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * lr
     *
     * @param  integer $lr
     * @return integer|Request
     */
    public function lr($lr = null)
    {
        if (is_null($lr)) {
            return $this->lr;
        } else {
            return $this->setLr($lr);
        }
    }

    /**
     * Set lr
     *
     * @param  integer $lr
     * @return Request
     */
    protected function setLr($lr)
    {
        $this->lr = $lr;
        return $this;
    }

    /**
     * Set/Get Localization
     *
     * @param  string $l10n
     * @return Request
     */
    public function l10n($l10n = null)
    {
        if (is_null($l10n)) {
            return $this->l10n;
        } else {
            return $this->setL10n($l10n);
        }
    }

    /**
     * Set localization
     *
     * @param  string $l10n
     * @return Request
     */
    protected function setL10n($l10n)
    {
        $this->l10n = $l10n;
        return $this;
    }

    /**
     * Set/Get Filter
     *
     * @param  string $filter
     * @return Request
     */
    public function filter($filter = null)
    {
        if (is_null($filter)) {
            return $this->filter;
        } else {
            return $this->setFilter($filter);
        }
    }

    /**
     * Set Filter
     *
     * @param  string $filter
     * @return Request
     */
    protected function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Sort by ..
     *
     * @param  string $sortBy
     * @return Request
     */
    public function sortBy($sortBy = null)
    {
        if (is_null($sortBy)) {
            return $this->sortBy;
        } else {
            return $this->setSortBy($sortBy);
        }
    }

    /**
     * Set sort by
     *
     * @param  string $sortBy
     * @return Request
     */
    protected function setSortBy($sortBy)
    {
        if ($sortBy == self::SORT_RLV || $sortBy == self::SORT_TM) {
            $this->sortBy = $sortBy;
            return $this;
        } else {
            throw new \InvalidArgumentException();
        }
    }


    /**
     * Setup group by
     *
     * @param  string $groupBy
     * @param  string $mode
     * @return Request
     */
    public function groupBy($groupBy = null, $mode = self::GROUP_MODE_FLAT)
    {
        if (is_null($groupBy)) {
            return $this->groupBy;
        } else {
            return $this->setGroupBy($groupBy);
        }
    }

    /**
     * Set group by
     *
     * @param  string $groupBy
     * @param  string $mode
     * @return Request
     */
    protected function setGroupBy($groupBy, $mode = self::GROUP_MODE_FLAT)
    {
        if ($groupBy == self::GROUP_DEFAULT || $groupBy == self::GROUP_SITE) {
            $this->groupBy = $groupBy;
            if ($groupBy == self::GROUP_DEFAULT) {
                $this->groupByMode = self::GROUP_MODE_FLAT;
            } else {
                $this->groupByMode = $mode;
            }
            return $this;
        } else {
            throw new \InvalidArgumentException();
        }
    }

    /**
     * Set option
     *
     * @param  string $option
     * @param  mixed $value
     * @return Request|mixed
     */
    public function option($option = null, $value = null)
    {
        if (is_null($option)) {
            return $this->getOption($option);
        } else {
            return $this->setOption($option, $value);
        }
    }

    /**
     * Set option
     *
     * @param  string $option
     * @param  mixed $value
     * @return Request
     */
    protected function setOption($option, $value = null)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * Get option
     *
     * @param string $option
     * @return mixed
     */
    protected function getOption($option)
    {
        if (isset($this->options[$option])) {
            return $this->options[$option];
        } else {
            return null;
        }
    }

    /**
     * Set/Get proxy fo request
     *
     * @param  string $host
     * @param  integer $port
     * @param  string $user
     * @param  string $pass
     * @return Request
     */
    public function proxy($host = '', $port = 80, $user = null, $pass = null)
    {
        if (is_null($host)) {
            return $this->getProxy();
        } else {
            return $this->setProxy($host, $port, $user, $pass);
        }
    }

    /**
     * Set proxy for request
     *
     * @param  string $host
     * @param  integer $port
     * @param  string $user
     * @param  string $pass
     * @return Request
     */
    protected function setProxy($host = '', $port = 80, $user = null, $pass = null)
    {
        $this->proxy = array(
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'pass' => $pass,
        );
        return $this;
    }

    /**
     * Get proxy settings
     *
     * @return Request
     */
    protected function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Apply proxy before each request
     * @param resource $ch
     */
    protected function applyProxy($ch)
    {
        curl_setopt_array(
            $ch,
            array(
                CURLOPT_PROXY => $this->proxy['host'],
                CURLOPT_PROXYPORT => $this->proxy['port'],
                CURLOPT_PROXYUSERPWD => $this->proxy['user'] . ':' . $this->proxy['pass']
            )
        );
    }

    /**
     * Send request
     *
     * @throws YandexXmlException
     * @return Response
     */
    public function send()
    {
        if (empty($this->query)
            && empty($this->host)
        ) {
            throw new YandexXmlException(YandexXmlException::EMPTY_QUERY);
        }

        $xml = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><request></request>");

        // add query to request
        $query = $this->query();

        // if isset "host"
        if ($this->host) {
            if (is_array($this->host)) {
                $host_query = '(host:"' . join('" | host:"', $this->host) . '")';
            } else {
                $host_query = 'host:"' . $this->host . '"';
            }

            if (!empty($query) && $this->host) {
                $query .= ' ' . $host_query;
            } elseif (empty($query) && $this->host) {
                $query .= $host_query;
            }
        }

        // if isset "site"
        if ($this->site) {
            if (is_array($this->site)) {
                $site_query = '(site:"' . join('" | site:"', $this->site) . '")';
            } else {
                $site_query = 'site:"' . $this->site . '"';
            }

            if (!empty($query) && $this->site) {
                $query .= ' ' . $site_query;
            } elseif (empty($query) && $this->site) {
                $query .= $site_query;
            }
        }

        // if isset "domain"
        if ($this->domain) {
            if (is_array($this->domain)) {
                $domain_query = '(domain:' . join(' | domain:', $this->domain) . ')';
            } else {
                $domain_query = 'domain:' . $this->domain;
            }
            if (!empty($query) && $this->domain) {
                $query .= ' ' . $domain_query;
            } elseif (empty($query) && $this->domain) {
                $query .= $domain_query;
            }
        }

        // if isset "cat"
        if ($this->cat) {
            $query .= ' cat:' . ($this->cat + 9000000);
        }

        // if isset "theme"
        if ($this->theme) {
            $query .= ' cat:' . ($this->theme + 4000000);
        }

        // if isset "geo"
        if ($this->geo) {
            $query .= ' cat:' . ($this->geo + 11000000);
        }

        $xml->addChild('query', $query);
        $xml->addChild('page', $this->page);
        $groupings = $xml->addChild('groupings');
        $groupby = $groupings->addChild('groupby');
        $groupby->addAttribute('attr', $this->groupBy);
        $groupby->addAttribute('mode', $this->groupByMode);
        $groupby->addAttribute('groups-on-page', $this->limit);
        $groupby->addAttribute('docs-in-group', 1);

        $xml->addChild('sortby', $this->sortBy);
        $xml->addChild('maxpassages', $this->options['maxpassages']);
        $xml->addChild('max-title-length', $this->options['max-title-length']);
        $xml->addChild('max-headline-length', $this->options['max-headline-length']);
        $xml->addChild('max-passage-length', $this->options['max-passage-length']);
        $xml->addChild('max-text-length', $this->options['max-text-length']);

        $this->request = $xml;


        // build GET data
        $getData = array(
                'user' => $this->user,
                'key' => $this->key,
            );

        if ($this->lr) {
            $getData['lr'] = $this->lr;
        }
        if ($this->l10n) {
            $getData['l10n'] = $this->l10n;
        }

        $url = $this->baseUrl .'?'. http_build_query($getData);

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
