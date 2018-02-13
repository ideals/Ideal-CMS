<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

class Pagination
{

    protected $next;

    protected $prev;

    protected $visiblePages = 4;

    public function getNext()
    {
        return $this->next;
    }

    public function getPages($itemsCount, $itemsOnPage, $page, $urlString, $urlParam)
    {
        $page = ($page == 0) ? 1 : $page;
        $pagesCount = ceil($itemsCount / $itemsOnPage); // кол-во страниц

        // Далее список всех страниц разбивается на блоки по $this->visiblePages страниц в каждом
        $actualBlock = floor(($page - 1) / $this->visiblePages); // текущий блок
        $endBlock = floor($pagesCount / $this->visiblePages); // последний блок

        // Номер первой отображаемой страницы
        $startPage = floor($actualBlock * $this->visiblePages) + 1;
        // Номер последней отображаемой страницы
        $endPage = ($actualBlock == $endBlock) ? $pagesCount : $startPage + $this->visiblePages - 1;

        // Если переменные в GET-запросе уже есть добавляем с амперсандом, иначе с вопросом
        if (strpos($urlString, '?') !== false) {
            $urlParam = '&' . $urlParam . '=';
        } else {
            $urlParam = '?' . $urlParam . '=';
        }

        $pages = array();

        // Если блок не первый, то ставим ссылку на последний элемент предыдущего блока
        if ($startPage > 1) {
            $pages[] = array(
                'url' => $urlString . $urlParam . ($startPage - 1),
                'num' => '…',
                'current' => 0
            );
        }

        // Составляем основной список листалки
        for ($n = $startPage; $n <= $endPage; $n++) {
            $pages[] = array(
                'url' => ($n == 1) ? $urlString : $urlString . $urlParam . $n,
                'num' => $n,
                'current' => ($n == $page) ? 1 : 0
            );
        }

        // Если последняя видимая цифра листалки не последняя, то ставим ссылку на следующий блок
        if ($endPage < $pagesCount) {
            $pages[] = array(
                'url' => $urlString . $urlParam . ($endPage + 1),
                'num' => '…',
                'current' => 0
            );
        }

        if ($page == 2) {
            $this->prev = $urlString;
        }

        if ($page > 2) {
            $this->prev = $urlString . $urlParam . ($page - 1);
        }

        if ($page < $pagesCount) {
            $this->next = $urlString . $urlParam . ($page + 1);
        }

        return $pages;
    }

    public function getPrev()
    {
        return $this->prev;
    }

    public function setVisiblePages($countPages)
    {
        $this->visiblePages = $countPages;
    }
}