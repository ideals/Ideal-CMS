<?php

namespace YandexXML;

/**
 * Class YandexXml for work with Yandex.XML
 *
 * @author   Anton Shevchuk <AntonShevchuk@gmail.com>
 * @link     http://anton.shevchuk.name
 * @link     http://yandex.hohli.com
 * @author   Mihail Bubnov <bubnov.mihail@gmail.com>
 * @package  YandexXml
 * @version  1.0.0
 * @created  Mar 19 11:22:50 EEST 2013
 */
class YandexXmlClient
{
    /**
     * Base url to service
     */
    const BASE_URL = 'https://yandex.ru/search/xml';

    /**
     * Response
     *
     * @see http://help.yandex.ru/xml/?id=362990
     * @var SimpleXML
     */
    public $response;

    /**
     * Wordstat array
     *
     * @var Array
     */
    public $wordstat = array();

    /**
     * Response in array
     *
     * @var Array
     */
    public $results = array();

    /**
     * Total results
     *
     * @var integer
     */
    public $total = null;

    /**
     * Total results in human form
     *
     * @var string
     */
    public $totalHuman = null;

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
     * cat
     *
     * @see http://search.yaca.yandex.ru/cat.c2n
     * @var integer
     */
    protected $cat;

    /**
     * theme
     *
     * @see http://help.yandex.ru/site/?id=1111797
     * @var integer
     */
    protected $theme;

    /**
     * geo
     *
     * @see http://search.yaca.yandex.ru/geo.c2n
     * @var integer
     */
    protected $geo;

    /**
     * lr
     *
     * @var integer
     */
    protected $lr;

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
     * @see http://help.yandex.ru/xml/?id=316625#sort
     * @var string
     */
    const SORT_RLV = 'rlv'; // relevation
    const SORT_TM = 'tm';  // time modification

    protected $sortby = 'rlv';

    /**
     * Group By  '' || 'd'
     *
     * @see http://help.yandex.ru/xml/?id=316625#group
     * @var string
     */
    const GROUP_DEFAULT = '';
    const GROUP_SITE = 'd'; // group by site
    protected $groupby = '';

    /**
     * Group mode   'flat' || 'deep' || 'wide'
     *
     * @var string
     */
    const GROUP_MODE_FLAT = 'flat';
    const GROUP_MODE_DEEP = 'deep';
    const GROUP_MODE_WIDE = 'wide';
    protected $groupby_mode = 'flat';

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
     * @var Array
     */
    protected $proxy = array();

    /**
     * __construct
     *
     * @param  string $user
     * @param  string $key
     * @throws YandexXmlException
     * @return YandexXmlClient
     */
    public function __construct($user, $key)
    {
        if (empty($user) or empty($key)) {
            throw new YandexXmlException(YandexXmlException::solveMessage(0));
        }
        $this->user = $user;
        $this->key = $key;
    }

    /**
     * query
     *
     * @access  public
     * @param  string $query
     * @return YandexXmlClient
     */
    public function query($query)
    {
        $this->query = $query;
        return $this;
    }

    /**
     * set query
     *
     * @access  public
     * @param  string $query
     * @return YandexXmlClient
     */
    public function setQuery($query)
    {
        return $this->query($query);
    }

    /**
     * getQuery
     *
     * @access  public
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * page
     *
     * @param  integer $page
     * @return YandexXmlClient
     */
    public function page($page)
    {
        $this->page = $page;
        return $this;
    }

    /**
     * set page
     *
     * @param  integer $page
     * @return YandexXmlClient
     */
    public function setPage($page)
    {
        return $this->page($page);
    }

    /**
     * getPage
     *
     * @return integer
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * limit
     *
     * @param  integer $limit
     * @return YandexXmlClient
     */
    public function limit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * set limit
     *
     * @param  integer $limit
     * @return YandexXmlClient
     */
    public function setLimit($limit)
    {
        return $this->limit($limit);
    }

    /**
     * getLimit
     *
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * host
     *
     * @param  string $host
     * @return YandexXmlClient
     */
    public function host($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * host
     *
     * @param  string $host
     * @return YandexXmlClient
     */
    public function setHost($host)
    {
        return $this->host($host);
    }

    /**
     * getHost
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * site
     *
     * @param  string $site
     * @return YandexXmlClient
     */
    public function site($site)
    {
        $this->site = $site;
        return $this;
    }

    /**
     * set site
     *
     * @param  string $site
     * @return YandexXmlClient
     */
    public function setSite($site)
    {
        return $this->site($site);
    }

    /**
     * getSite
     *
     * @return string
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * domain
     *
     * @param  string $domain
     * @return YandexXmlClient
     */
    public function domain($domain)
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * set domain
     *
     * @param  string $domain
     * @return YandexXmlClient
     */
    public function setDomain($domain)
    {
        return $this->domain($domain);
    }

    /**
     * getDomain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * cat
     *
     * @param  integer $cat
     * @return YandexXmlClient
     */
    public function cat($cat)
    {
        $this->cat = $cat;
        return $this;
    }

    /**
     * set cat
     *
     * @param  integer $cat
     * @return YandexXmlClient
     */
    public function setCat($cat)
    {
        return $this->cat($cat);
    }

    /**
     * getCat
     *
     * @return integer
     */
    public function getCat()
    {
        return $this->cat;
    }

    /**
     * geo
     *
     * @param  integer $geo
     * @return YandexXmlClient
     */
    public function geo($geo)
    {
        $this->geo = $geo;
        return $this;
    }

    /**
     * set geo
     *
     * @param  integer $geo
     * @return YandexXmlClient
     */
    public function setGeo($geo)
    {
        return $this->geo($geo);
    }

    /**
     * getGeo
     *
     * @return integer
     */
    public function getGeo()
    {
        return $this->geo;
    }

    /**
     * theme
     *
     * @param  integer $theme
     * @return YandexXmlClient
     */
    public function theme($theme)
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * set theme
     *
     * @param  integer $theme
     * @return YandexXmlClient
     */
    public function setTheme($theme)
    {
        return $this->theme($theme);
    }

    /**
     * getTheme
     *
     * @return integer
     */
    public function getTheme()
    {
        return $this->theme;
    }

    /**
     * lr
     *
     * @param  integer $lr
     * @return YandexXmlClient
     */
    public function lr($lr)
    {
        $this->lr = $lr;
        return $this;
    }

    /**
     * set lr
     *
     * @param  integer $lr
     * @return YandexXmlClient
     */
    public function setLr($lr)
    {
        return $this->lr($lr);
    }

    /**
     * getLr
     *
     * @return integer
     */
    public function getLr()
    {
        return $this->lr;
    }

    /**
     * sortby
     *
     * @param  string $sortby
     * @return YandexXmlClient
     */
    public function sortby($sortby)
    {
        if ($sortby == self::SORT_RLV || $sortby == self::SORT_TM) {
            $this->sortby = $sortby;
        }
        return $this;
    }

    /**
     * set sortby
     *
     * @param  string $sortby
     * @return YandexXmlClient
     */
    public function setSortby($sortby)
    {
        return $this->sortby($sortby);
    }

    /**
     * getSortby
     *
     * @return string
     */
    public function getSortby()
    {
        return $this->sortby;
    }

    /**
     * setup groupby
     *
     * @param  string $groupby
     * @param  string $mode
     * @return YandexXmlClient
     */
    public function groupby($groupby, $mode = self::GROUP_MODE_FLAT)
    {
        if ($groupby == self::GROUP_DEFAULT || $groupby == self::GROUP_SITE) {
            $this->groupby = $groupby;
            if ($groupby == self::GROUP_DEFAULT) {
                $this->groupby_mode = self::GROUP_MODE_FLAT;
            } else {
                $this->groupby_mode = $mode;
            }
        }
        return $this;
    }

    /**
     * setup groupby
     *
     * @param  string $groupby
     * @param  string $mode
     * @return YandexXmlClient
     */
    public function setGroupby($groupby, $mode = self::GROUP_MODE_FLAT)
    {
        return $this->groupby($groupby, $mode);
    }

    /**
     * get groupby
     *
     * @return string
     */
    public function getGroupby()
    {
        return $this->groupby;
    }

    /**
     * getGroupbyMode
     *
     * @return string
     */
    public function getGroupbyMode()
    {
        return $this->groupby_mode;
    }

    /**
     * free setter for options
     *
     * @param  string $option
     * @param  mixed $value
     * @return YandexXmlClient
     */
    public function set($option, $value = null)
    {
        return $this->setOption($option, $value);
    }

    /**
     * free setter for options
     *
     * @param  string $option
     * @param  mixed $value
     * @return YandexXmlClient
     */
    public function setOption($option, $value = null)
    {
        $this->options[$option] = $value;
        return $this;
    }

    /**
     * set proxe fo request
     * @param  type $host
     * @param  type $port
     * @param  type $user
     * @param  type $passwd
     * @return YandexXmlClient
     */
    public function setProxy($host = '', $port = 80, $user = null, $passwd = null)
    {
        $this->proxy = array(
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'passwd' => $passwd,
        );

        return $this;
    }

    /**
     * Apply proxy before each request
     * @param Resource $ch
     */
    protected function applyProxy($ch)
    {
        $host = empty($this->proxy['host']) ? '' : $this->proxy['host'];
        $port = empty($this->proxy['port']) ? 0 : $this->proxy['port'];
        $user = empty($this->proxy['user']) ? 0 : $this->proxy['user'];
        $passwd = empty($this->proxy['passwd']) ? 0 : $this->proxy['passwd'];
        curl_setopt($ch, CURLOPT_PROXY, $host);
        curl_setopt($ch, CURLOPT_PROXYPORT, $port);
        // Добавил проверку существования этих именованных констант перед использованием. У меня их почему-то нет.
        if (defined('CURLOPT_PROXYUSERNAME')) {
            curl_setopt($ch, CURLOPT_PROXYUSERNAME, $user);
        }
        if (defined('CURLOPT_PROXYPASSWORD')) {
            curl_setopt($ch, CURLOPT_PROXYPASSWORD, $passwd);
        }
    }

    /**
     * send request
     * @throws YandexXmlException
     * @return YandexXmlClient
     */
    public function request()
    {
        if (empty($this->query)
            && empty($this->host)
        ) {
            throw new YandexXmlException(YandexXmlException::solveMessage(2));
        }

        $xml = new \SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?><request></request>");

        // add query to request
        $query = $this->query;

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
        $groupby->addAttribute('attr', $this->groupby);
        $groupby->addAttribute('mode', $this->groupby_mode);
        $groupby->addAttribute('groups-on-page', $this->limit);
        $groupby->addAttribute('docs-in-group', 1);

        $xml->addChild('sortby', $this->sortby);
        $xml->addChild('maxpassages', $this->options['maxpassages']);
        $xml->addChild('max-title-length', $this->options['max-title-length']);
        $xml->addChild('max-headline-length', $this->options['max-headline-length']);
        $xml->addChild('max-passage-length', $this->options['max-passage-length']);
        $xml->addChild('max-text-length', $this->options['max-text-length']);

        $this->request = $xml;

        $ch = curl_init();

        $url = self::BASE_URL
            . '?user=' . $this->user
            . '&key=' . $this->key;

        if ($this->lr) {
            $url .= '&lr=' . $this->lr;
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/xml"));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/xml"));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML());
        curl_setopt($ch, CURLOPT_POST, true);
        $this->applyProxy($ch);
        $data = curl_exec($ch);

        $this->response = new \SimpleXMLElement($data);
        $this->response = $this->response->response;

        $this->checkErrors();
        $this->bindData();

        return $this;
    }

    /**
     * Get last request as string
     *
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * check response errors
     * @throws YandexXmlException
     */
    protected function checkErrors()
    {
        if (isset($this->response->error)) {
            $code = (int) $this->response->error->attributes()->code[0];
            throw new YandexXmlException(YandexXmlException::solveMessage($code, $this->response->error));
        }
    }

    /**
     * bindData
     *
     * @return void
     */
    protected function bindData()
    {
        $wordstat = preg_split('/,/', $this->response->wordstat);
        $this->wordstat = array();
        if (empty($this->response->wordstat)) {
            return;
        }
        foreach ($wordstat as $word) {
            list($word, $count) = preg_split('/:/', $word);
            $this->wordstat[$word] = intval(trim($count));
        }
    }

    /**
     * get total results
     *
     * @return integer
     */
    public function total()
    {
        return $this->getTotal();
    }

    /**
     * get total results
     *
     * @return integer
     */
    public function getTotal()
    {
        if ($this->total === null) {
            $res = $this->response->xpath('found[attribute::priority="all"]');
            $this->total = (int) $res[0];
        }
        return $this->total;
    }

    /**
     * get total results in human form
     *
     * @return string
     */
    public function totalHuman()
    {
        return $this->getTotalHuman();
    }

    /**
     * get total results in human form
     *
     * @return string
     */
    public function getTotalHuman()
    {
        if ($this->totalHuman === null) {
            $res = $this->response->xpath('found-human');
            $this->totalHuman = $res[0];
        }
        return $this->totalHuman;
    }

    /**
     * get total pages
     *
     * @return integer
     */
    public function pages()
    {
        return $this->getPages();
    }

    /**
     * get total pages
     *
     * @return integer
     */
    public function getPages()
    {
        if (empty($this->pages)) {
            $this->pages = ceil($this->getTotal() / $this->limit);
        }
        return $this->pages;
    }

    /**
     * return associated array of groups
     *
     * @return array
     */
    public function results()
    {
        return $this->getResults();
    }

    /**
     * return associated array of groups
     *
     * @return array
     */
    public function getResults()
    {
        $this->results = array();
        if ($this->response) {
            foreach ($this->response->results->grouping->group as $group) {
                $res = new \stdClass();
                $res->url = (string) $group->doc->url;
                $res->domain = (string) $group->doc->domain;
                $res->title = isset($group->doc->title) ? $this->highlight($group->doc->title) : $res->url;
                $res->headline = isset($group->doc->headline) ? $this->highlight($group->doc->headline) : null;
                $res->passages = isset($group->doc->passages->passage) ? $this->highlight($group->doc->passages) : null;
                $res->sitelinks = isset($group->doc->snippets->sitelinks->link)
                    ? $this->highlight($group->doc->snippets->sitelinks->link)
                    : null;

                $this->results[] = $res;
            }
        }

        return $this->results;
    }

    /**
     * return pagebar array
     *
     * @return array
     */
    public function pageBar()
    {
        return $this->getPageBar();
    }

    /**
     * return pagebar array
     *
     * @return array
     */
    public function getPageBar()
    {
        // FIXME: not good
        $pages = $this->getPges();

        if ($pages < 10) {
            $pagebar = array_fill(0, $pages, array('type' => 'link', 'text' => '%d'));
            $pagebar[$this->page] = array('type' => 'current', 'text' => '<b>%d</b>');
        } elseif ($pages >= 10 && $this->page < 9) {
            $pagebar = array_fill(0, 10, array('type' => 'link', 'text' => '%d'));
            $pagebar[$this->page] = array('type' => 'current', 'text' => '<b>%d</b>');
        } elseif ($pages >= 10 && $this->page >= 9) {
            $pagebar = array_fill(0, 2, array('type' => 'link', 'text' => '%d'));
            $pagebar[] = array('type' => 'text', 'text' => '..');
            $pagebar += array_fill($this->page - 2, 2, array('type' => 'link', 'text' => '%d'));
            if ($pages > ($this->page + 2)) {
                $pagebar += array_fill($this->page, 2, array('type' => 'link', 'text' => '%d'));
            }
            $pagebar[$this->page] = array('type' => 'current', 'text' => '<b>%d</b>');
        }

        return $pagebar;
    }

    /**
     * highlight text
     *
     * @param  SimpleXML $xml
     * @return String
     */
    public static function highlight($xml)
    {
        // FIXME: very strangely method
        $text = $xml->asXML();

        $text = str_replace('<hlword>', '<strong>', $text);
        $text = str_replace('</hlword>', '</strong>', $text);
        $text = strip_tags($text, '<strong>');

        return $text;
    }
}
