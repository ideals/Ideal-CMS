<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Field;

abstract class Model extends Core\Model
{

    protected $pageNum = 0;

    public $metaTags = array(
        'robots' => 'index, follow'
    );

    public abstract function detectPageByUrl($path, $url);

    public function getBreadCrumbs()
    {
        $path = $this->path;
        $path[0]['name'] = $path[0]['startName'];

        if (isset($this->path[1]['url']) and ($this->path[1]['url'] == '/') and count($path) == 2) {
            // На главной странице хлебные крошки отображать не надо
            return '';
        }

        // Отображение хлебных крошек
        $pars = array();
        $breadCrumbs = array();
        $url = new Field\Url\Model();
        foreach ($path as $v) {
            if (isset($v['is_skip']) && $v['is_skip']) {
                continue;
            }
            $url->setParentUrl($pars);
            $link = $url->getUrl($v);
            $pars[] = $v;
            if ($link == '/') {
                $breadCrumbs[] = array(
                    'link' => $link,
                    'name' => $v['startName']
                );
            } else {
                $breadCrumbs[] = array(
                    'link' => $link,
                    'name' => $v['name']
                );
            }
        }
        return $breadCrumbs;
    }

    public function getHeader()
    {
        $header = '';
        if (isset($this->pageData['template']['content'])) {
            // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
            list($header, $text) = $this->extractHeader($this->pageData['template']['content']);
            $this->pageData['template']['content'] = $text;
        }

        if ($header == '') {
            // Если заголовка H1 в тексте нет, берём его из названия name
            $header = $this->pageData['name'];
        }
        return $header;
    }

    public function extractHeader($text)
    {
        $header = '';
        if (preg_match('/<h1.*>(.*)<\/h1>/isU', $text, $headerArray)) {
            $text = preg_replace('/<h1.*>(.*)<\/h1>/isU', '', $text, 1);
            $header = $headerArray[1];
        }
        return array($header, $text);
    }

    public function getMetaTags($xhtml = false)
    {
        $meta = '';
        $xhtml = ($xhtml) ? '/' : '';
        $end = end($this->path);

        if (isset($end['description']) and $end['description'] != '' and 1 === $this->pageNum) {
            $meta .= '<meta name="description" content="'
                . str_replace('"', '&quot;', $end['description'])
                . '" ' . $xhtml . '>';
        }

        if (isset($end['keywords']) and $end['keywords'] != '' and 1 === $this->pageNum) {
            $meta .= '<meta name="keywords" content="'
                . str_replace('"', '&quot;', $end['keywords'])
                . '" ' . $xhtml . '>';
        }

        foreach ($this->metaTags as $tag => $value) {
            $meta .= '<meta name="' . $tag . '" content="'
                . $value . '" ' . $xhtml . '>';
        }

        return $meta;
    }

    public function getTitle()
    {
        $end = $this->pageData;
        $concat = ($this->pageNum > 1) ? " | Страница {$this->pageNum}" : '';
        if (isset($end['title']) and $end['title'] != '') {
            return $end['title'] . $concat;
        } else {
            return $end['name'] . $concat;
        }
    }

    public function setPageNum($page_num)
    {
        $page_num = intval($page_num);
        if (0 === $page_num) {
            $page_num = 1;
        }
        $this->pageNum = $page_num;
    }
}
