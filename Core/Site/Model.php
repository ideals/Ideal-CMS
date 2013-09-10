<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Core\Util;
use Ideal\Field;

abstract class Model extends Core\Model
{
    public $metaTags = array(
        'robots' => 'index, follow');
    protected $templatesVars = array();

    public function getTitle()
    {
        $end = end($this->path);
        if (isset($end['title']) AND $end['title'] != '') {
            return $end['title'];
        } else {
            return $end['name'];
        }
    }


    public function getHeader()
    {
        $templatesVars = $this->getTemplatesVars();

        $header = '';
        if (isset($templatesVars['template']['content'])) {
            list($header, $text) = $this->extractHeader($templatesVars['template']['content']);
            $templatesVars['template']['content'] = $text;
        }

        if ($header == '') {
            $end = end($this->path);
            $header = $end['name'];
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


    public function getTemplatesVars()
    {
        if (count($this->templatesVars) > 0) {
            return $this->templatesVars;
        }
        $end = end($this->path);
        $templatesVars = array();
        foreach ($this->fields as $k => $v) {
            // Пропускаем все поля, которые не являются шаблоном
            if (strpos($v['type'], '_Template') === false) continue;
            $className = Util::getClassName($end[$k], 'Template') . '\\Model';
            $prevStructure = $end['prev_structure'] . '-' . $end['ID'];
            $template = new $className($prevStructure);
            $templatesVars[$k] = $template->getObject($this);
        }
        return $templatesVars;
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
            $link = '';
            if ($v['is_skip']) continue;
            if (!isset($v['is_skip']) OR $v['is_skip'] == 0) {
                $url->setParentUrl($pars);
                $link = $url->getUrl($v);
            }
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