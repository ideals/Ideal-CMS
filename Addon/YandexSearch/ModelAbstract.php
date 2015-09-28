<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\YandexSearch;

use Ideal\Addon;
use Ideal\Core\Config;
use Ideal\Core\View;
use Ideal\Structure\User;
use Ideal\Core\Request;
use YandexXML\YandexXmlClient;
use Exception;

/**
 * Класс аддона, обеспечивающий поиск по сайту
 *
 * Содержит в себе обращение к сервису Яндекс.XML либо по настройкам аддона, либо по глобальным настройкам
 * в CMS (файл config.php). Аддон содержит шаблон index.twig, подключаемый для генерации поля content аддона,
 * поэтому аддон можно подлючать как обычный аддон Page, для которого никакой дополнительной кастомизации
 * в общем шаблоне не требуется.
 */
class ModelAbstract extends Addon\AbstractModel
{

    /** @var int Общее количесвто результатов поиска */
    protected $listCount = 0;

    /**
     * Получение данных аддона с выполнение всех действий (в данном случае — запроса к Яндексу)
     *
     * @return array Все данные аддона и сгенерированное поле content с отображаемым html-кодом
     */
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));

        if ($mode[3] != 'Site') {
            // Отображение поиска нужно только для фронтенда, в бэкенде просто возвращаем данные из БД
            return $this->pageData;
        }

        $config = Config::getInstance();

        // Подключаем шаблон аддона
        $tplRoot = dirname(stream_resolve_include_path('Addon/YandexSearch/index.twig'));
        $view = new View($tplRoot, $config->cache['templateSite']);
        $view->loadTemplate('index.twig');

        // Логин и ключ от сервиса Яндекс
        $yandexLogin = trim($this->pageData['yandexLogin']);
        $yandexKey = trim($this->pageData['yandexKey']);

        // Адрес прокси скрипта
        $proxyUrl = trim($this->pageData['proxyUrl']);
        if (empty($proxyUrl)) {
            $proxyUrl = trim($config->yandex['proxyUrl']);
        }

        // Номер отображаемой страницы
        $request = new Request();
        $page = intval($request->{'num'});
        $page = ($page == 0) ? 1 : $page;
        $page--;

        // Поисковый запрос
        $request = new Request();
        $query = trim(strval($request->{'query'}));

        if (!empty($query)) {
            if (empty($yandexLogin) || empty($yandexKey)) {
                $yandexLogin = trim($config->yandex['yandexLogin']);
                $yandexKey = trim($config->yandex['yandexKey']);
            }

            // Для фронтенда рендерим результат поиска
            if (!empty($yandexLogin) && !empty($yandexKey) && !empty($query)) {
                $yandex = new YandexXmlClient($yandexLogin, $yandexKey);

                // Параметр необходимый для получения листалки
                $elementsSite = $this->pageData['elements_site'];
                $this->params['elements_site'] = !empty($elementsSite) ? $elementsSite : 15;

                // Отлавливаем исключения и отдаём их на страницу
                try {
                    $yandex->query($query) // устанавливаем поисковый запрос
                    ->site($config->domain) // ограничиваемся поиском по сайту
                    ->setProxyUrl($proxyUrl)
                        ->page($page)
                        ->limit($this->params['elements_site']) // результатов на странице
                        ->request()                             // отправляем запрос
                    ;
                } catch (Exception $e) {
                    $view->message = $e->getMessage();
                }

                $list = $yandex->results();
                $list = $this->view($list);

                // Передаём данные в шаблон для рендера поиска
                $view->total = $this->listCount = $yandex->total();
                $view->parts = $list;
                $view->query = $query;
                $view->pager = $this->getPager('num');

            } else {
                $view->message = 'Поле логин или ключ от яндекса имеет пустое значене';
            }
        } else {
            $view->message = 'Пустой поисковый запрос';
        }
        $this->pageData['content'] .= $view->render();

        return $this->pageData;
    }

    /**
     * Используется в методе "getPager"
     *
     * @return int Общее количесвто результатов поиска
     */
    public function getListCount()
    {
        return $this->listCount;
    }

    /**
     * Построение списка страниц в виде массива на основании ответа яндекса в виде XML с выделением пассажей
     *
     * @param array $list Массив классов, содержащих найденные страницы
     * @return array Массив найденных страниц, с выделенными пассажами
     */
    protected function view($list)
    {
        $result = array();
        foreach ($list as $k => $v) {
            $result[$k]['url'] = (string) $v->url;
            $result[$k]['title'] = (string) $v->title;
            if (is_array($v->passages)) {
                foreach ($v->passages as $passage) {
                    $result[$k]['passages'][] = $passage;
                }
            } else {
                $result[$k]['passages'][] = $v->passages;
            }
        }
        return $result;
    }
}
