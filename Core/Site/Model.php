<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Core\Util;
use Ideal\Field;

abstract class Model extends Core\Model
{
    public $metaTags = array(
        'robots' => 'index, follow');

    public function getTitle()
    {
        $end = $this->pageData;
        if (isset($end['title']) AND $end['title'] != '') {
            return $end['title'];
        } else {
            return $end['name'];
        }
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

        if (isset($end['description']) AND $end['description'] != '') {
            $meta .= '<meta name="description" content="'
                   . str_replace('"', '&quot;', $end['description'])
                   . '" ' . $xhtml . '>';
        }

        if (isset($end['keywords']) AND $end['keywords'] != '') {
            $meta .= '<meta name="keywords" content="'
                   . str_replace('"', '&quot;', $end['keywords'])
                   . '" ' . $xhtml . '>';
        }

        foreach($this->metaTags as $tag => $value) {
            $meta .= '<meta name="' . $tag . '" content="'
                  . $value . '" ' . $xhtml . '>';
        }

        return $meta;
    }


    public function getBreadCrumbs()
    {
        $path = $this->path;
        $path[0]['name'] = $path[0]['startName'];


        if (isset($this->path[1]['url']) AND ($this->path[1]['url'] == '/') AND count($path) == 2) {
            // На главной странице хлебные крошки отображать не надо
            return '';
        }

        // Отображение хлебных крошек
        $pars = array();
        $breadCrumbs = array();
        $url = new Field\Url\Model();
        foreach ($path as $v) {
            if (isset($v['is_skip']) && $v['is_skip']) continue;
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


    abstract function detectPageByUrl($url, $path);
}