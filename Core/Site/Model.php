<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Field;

abstract class Model extends Core\Model
{

    public $metaTags = array(
        'robots' => 'index, follow'
    );

    abstract public function detectPageByUrl($path, $url);

    public function getBreadCrumbs()
    {
        $path = $this->path;
        $path[0]['name'] = $path[0]['startName'];

        if (isset($this->path[1]['url']) && ($this->path[1]['url'] == '/') && count($path) == 2) {
            // На главной странице хлебные крошки отображать не надо
            return '';
        }

        // Отображение хлебных крошек
        $pars = array();
        $breadCrumbs = array();
        $url = new Field\Url\Model();
        foreach ($path as $v) {
            if (isset($v['is_skip']) && $v['is_skip'] && isset($v['is_not_menu']) && $v['is_not_menu']) {
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
        // Если есть шаблон с контентом, пытаемся из него извлечь заголовок H1
        if (isset($this->pageData['content']) && !empty($this->pageData['content'])) {
            list($header, $text) = $this->extractHeader($this->pageData['content']);
            $this->pageData['content'] = $text;
        } elseif (!empty($this->pageData['addon'])) {
            // Последовательно пытаемся получить заголовок из всех аддонов до первого найденного
            $addons = json_decode($this->pageData['addon']);
            for ($i = 0; $i < count($addons); $i++) {
                if (isset($this->pageData['addons'][$i]['content'])
                    && $this->pageData['addons'][$i]['content'] !== ''
                ) {
                    list($header, $text) = $this->extractHeader($this->pageData['addons'][$i]['content']);
                    if (!empty($header)) {
                        $this->pageData['addons'][$i]['content'] = $text;
                        break;
                    }
                }
            }
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

        if (isset($end['description']) && $end['description'] != '' && $this->pageNum === 1) {
            $meta .= '<meta name="description" content="'
                . str_replace('"', '&quot;', $end['description'])
                . '" ' . $xhtml . '>';
        }

        if (isset($end['keywords']) && $end['keywords'] != '' && $this->pageNum === 1) {
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

    /**
     * Получение тайтла (<title>) для страницы
     *
     * Тайтл может быть либо задан через параметр title в $this->pageDate, а если title отсутствует или пуст,
     * то тайтл генерируется из параметра name.
     * Кроме того, в случае, если запрашивается не первая страница листалки (новости, статьи и т.п.), то
     * этот метод добавляет суффикс листалки с указанием номера страницы
     *
     * @return string Тайтл для страницы
     */
    public function getTitle()
    {
        $end = $this->pageData;
        $concat = ($this->pageNum > 1) ? str_replace('[N]', $this->pageNum, $this->pageNumTitle) : '';
        if (isset($end['title']) && $end['title'] != '') {
            return $end['title'] . $concat;
        } else {
            return $end['name'] . $concat;
        }
    }
}
