<?php
namespace Ideal\Addon\YandexSearch;

use Ideal\Core\Config;
use Ideal\Core\View;
use Ideal\Structure\User;
use Ideal\Core\Request;
use YandexXML\YandexXmlClient;
use YandexXML\YandexXmlException;
use Exception;

class ModelAbstract extends \Ideal\Addon\AbstractModel
{
    /** @var int Общее количесвто результатов поиска */
    protected $listCount = 0;

    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));

        if ($mode[3] == 'Site') {
            $config = Config::getInstance();

            $tplRoot = dirname(stream_resolve_include_path('Addon/YandexSearch/index.twig'));
            $view = new View($tplRoot, $config->cache['templateSite']);
            $view->loadTemplate('index.twig');

            // Логин от сервиса Яндекс
            $yandexLogin = trim($this->pageData['yandexLogin']);

            // Ключ от сервиса Яндекс
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
            $query = trim(strval($request->{'search'}));

            if (empty($query)) {
                $this->pageData['content'] .= 'Пустой поисковый запрос';
            } else {
                if (empty($yandexLogin) || empty($yandexKey)) {
                    $yandexLogin = trim($config->yandex['yandexLogin']);
                    $yandexKey = trim($config->yandex['yandexKey']);
                }

                // Для фронтенда рендерим результат поиска
                if (!empty($yandexLogin) && !empty($yandexKey) && !empty($query)) {
                    $yandex = new YandexXmlClient($yandexLogin, $yandexKey);

                    // Отлавливаем исключения и отдаём их на страницу
                    try {
                        $yandex->query($query)// устанавливаем поисковый запрос
                        ->site($config->domain)// ограничиваемся поиском по сайту
                        ->setProxyUrl($proxyUrl)
                            ->page($page)
                            ->limit($this->pageData['elements_site'])// результатов на странице
                            ->request()                            // отправляем запрос
                        ;
                    } catch (YandexXmlException $e) {
                        $this->pageData['content'] .= $e->getMessage();
                    } catch (Exception $e) {
                        $this->pageData['content'] .= $e->getMessage();
                    }

                    // Параметр необходимый для получения листалки
                    $elementsSite = $this->pageData['elements_site'];
                    $this->params['elements_site'] = !empty($elementsSite) ? $elementsSite : 15;

                    $list = $yandex->results();
                    $list = $this->view($list);
                    $this->listCount = $yandex->total();

                    // Передаём данные в шаблон для рендера поиска
                    $view->parts = $list;
                    $view->query = $query;
                    $view->pager = $this->getPager('num');

                } else {
                    $this->pageData['content'] .= 'Поле логин или ключ от яндекса имеет пустое значене';
                }
            }
            $this->pageData['content'] .= $view->render();
        }
        return $this->pageData;
    }

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

    public function getListCount()
    {
        return $this->listCount;
    }
}
