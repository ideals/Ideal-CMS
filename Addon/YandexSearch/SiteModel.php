<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\YandexSearch;

use App\Core\Logger;
use Ideal\Addon;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\View;
use Ideal\YandexSearch\Client;
use Ideal\YandexSearch\Exception\VeryLongQueryException;
use Ideal\YandexSearch\WebSearchRequest;

/**
 * Класс аддона, обеспечивающий поиск по сайту
 *
 * Содержит в себе обращение к сервису Яндекс.XML либо по настройкам аддона, либо по глобальным настройкам
 * в CMS (файл config.php). Аддон содержит шаблон index.twig, подключаемый для генерации поля content аддона,
 * поэтому аддон можно подключать как обычный аддон Page, для которого никакой дополнительной кастомизации
 * в общем шаблоне не требуется.
 */
class SiteModel extends Addon\AbstractSiteModel
{
    /** @var int Общее количество результатов поиска */
    protected $listCount = 0;

    /**
     * Получение данных аддона с выполнением всех действий (в данном случае — запроса к Яндексу)
     *
     * @return array Все данные аддона и сгенерированное поле content с отображаемым html-кодом
     */
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));

        if ($mode[3] !== 'Site') {
            // Отображение поиска нужно только для фронтенда, в бэкенде просто возвращаем данные из БД
            return $this->pageData;
        }

        $config = Config::getInstance();

        // Подключаем шаблон аддона
        $tplRoot = dirname(stream_resolve_include_path('Addon/YandexSearch/index.twig'));
        $view = new View($tplRoot, $config->cache['templateSite']);
        $view->loadTemplate('index.twig');

        // Номер отображаемой страницы
        $request = new Request();
        $page = (int) $request->num;
        $page = ($page === 0) ? 1 : $page;
        $page--;

        // Поисковый запрос
        $request = new Request();
        $query = trim((string) $request->query);
        $view->query = $query;

        if (!empty($query)) {
            // Параметр необходимый для получения листалки
            $elementsSite = $this->pageData['elements_site'];
            $this->params['elements_site'] = !empty($elementsSite) ? $elementsSite : 15;

            try {
                $request = (new WebSearchRequest(
                    'site:' . $config->domain . ' "' . str_replace('"', '', $query) . '"',
                ))
                    ->setPerPage((int) $this->params['elements_site'])
                    ->setPage($page);
            } catch (VeryLongQueryException $e) {
                return [];
            }

            $client = new Client(
                $_ENV['YANDEX_CLOUD_SEARCH_URL'],
                $_ENV['YANDEX_CLOUD_SEARCH_API_KEY'],
                Logger::getInstance(),
            );
            $response = $client->send($request);

            // Передаём данные в шаблон для рендера поиска
            $view->total = $this->listCount = $response->getDocsTotal();
            $view->parts = $response->getDocuments();
            $view->pager = $this->getPager('num');
            $page++;
            $view->startList = $page * $this->pageData['elements_site'] - $this->pageData['elements_site'] + 1;
        }

        $this->pageData['content'] .= $view->render();

        return $this->pageData;
    }

    /**
     * Используется в методе "getPager"
     *
     * @return int Общее количество результатов поиска
     */
    public function getListCount()
    {
        return $this->listCount;
    }
}
