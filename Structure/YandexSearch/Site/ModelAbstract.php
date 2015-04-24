<?php
namespace Ideal\Structure\YandexSearch\Site;

use Ideal\Core\Config;
use Ideal\Structure\User;
use YandexXML\YandexXmlClient;
use YandexXML\YandexXmlException;
use Exception;

class ModelAbstract extends \Ideal\Core\Site\Model
{

    public $cid;

    // Поисковый запрос
    protected $query;
    protected $listCount = 0;

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        $config = Config::getInstance();
        $yandex = new YandexXmlClient($config->yandex['yandexLogin'], $config->yandex['yandexKey']);

        if (empty($this->query)) {
            throw new \Exception('Пустой поисковый запрос');
        }

        // Отлавливаем исключения и отдаём их на страницу
        try {
            $yandex->query($this->query)// устанавливаем поисковый запрос
            ->site($config->domain)// ограничиваемся поиском по сайту
            ->page($page)
                ->limit($this->params['elements_cms'])// результатов на странице
                ->request()                            // отправляем запрос
            ;
        } catch (YandexXmlException $e) {
            return $e->getMessage();
        } catch (Exception $e) {
            return $e->getMessage();
        }

        $list = $yandex->results();
        $list = $this->view($list);
        $this->listCount = $yandex->total();
        return $list;
    }

    protected function view($list)
    {
        $result = array();
        foreach ($list as $k => $v) {
            $result[$k]['url'] = (string) $v->url;
            $result[$k]['title'] = (string) $v->title;
            foreach ($v->passages as $passage) {
                $result[$k]['passages'][] = YandexXmlClient::highlight($passage);
            }
        }
        return $result;
    }

    public function getListCount()
    {
        return $this->listCount;
    }

    /**
     * Устанавливаем поисковый запрос
     *
     * @param $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    public function detectPageByUrl($path, $url)
    {
    }

    public function getStructureElements()
    {
        return array();
    }
}
