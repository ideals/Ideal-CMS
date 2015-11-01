<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Error404;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Structure\User;

/**
 * Класс для обработки 404-ых ошибок
 */
class Model
{
    /** @var string Адрес запрошенной страницы */
    protected $url = '';

    /** @var bool Флаг отпрваки сообщения о 404ой ошибке */
    protected $send404 = true;

    /** @var mixed Признак доступности файла со списком известных 404ых.
     * Содержит инфоормацию из этого файла, в случае его доступности*/
    protected $known404 = false;

    /**
     * Устанавливает адрес запрошенной страницы
     *
     * @param string $url Адрес запрошенной страницы
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Возвращает значение флага отпрваки сообщения о 404ой ошибке
     */
    public function send404()
    {
        return $this->send404;
    }

    /**
     * Проверяет наличие адреса запрошенной страницы среди уже известных 404-ых
     *
     * @return bool Флаг наличия адреса запрошенной страницы среди уже известных 404-ых
     */
    public function checkAvailability404()
    {
        $config = Config::getInstance();
        $is404 = false;

        // Признак запуска процесса обработки 404ой ошибки. Зависит от параметра "Уведомление о 404ых ошибках"
        $init404Process = true;

        if (isset($config->cms['error404Notice'])) {
            $init404Process = $config->cms['error404Notice'];
        }

        // Инициируем процесс обработки 404-ых ошибок только если включена галка "Уведомление о 404ых ошибках"
        if ($init404Process) {
            // Определяем есть ли запрошенный адрес среди уже известных 404
            if (file_exists(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php')) {
                $this->known404 = new \Ideal\Structure\Service\SiteData\ConfigPhp();
                $this->known404->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');
                $known404Params = $this->known404->getParams();
                $known404List = array_filter(explode("\n", $known404Params['known']['arr']['known404']['value']));
                $matchesRules = self::matchesRules($known404List, $this->url);
                if (!empty($matchesRules)) {
                    $is404 = true;
                    $this->send404 = false;
                    // Если пользователь залогинен, то удаляем данный адрес из известных 404-ых
                    $user = new User\Model();
                    if ($user->checkLogin() !== false) {
                        foreach ($matchesRules as $key => $value) {
                            unset($known404List[$key]);
                        }
                        $known404Params['known']['arr']['known404']['value'] = implode("\n", $known404List);
                        $this->known404->setParams($known404Params);
                        $this->known404->saveFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');
                        $this->send404 = true;
                    }
                }
            }
        }
        return $is404;
    }

    /**
     * Сохраняет информацию о 404 ошибке в справочник/файл
     */
    public function save404()
    {
        $db = DB::getInstance();
        $config = Config::getInstance();
        $error404Structure = $config->getStructureByName('Ideal_Error404');
        $error404Table = $config->db['prefix'] . 'ideal_structure_error404';
        $user = new User\Model();
        $isAdmin = $user->checkLogin();
        $this->send404 = true;

        // Запускаем процесс обработки 404 страницы только если
        // существует структура "Ideal_Error404",
        // существует файл known404.php,
        // в настройках включена галка "Уведомление о 404ых ошибках",
        // пользователь не залогинен в админку
        if ($error404Structure !== false && $this->known404 !== false && !$isAdmin) {
            $known404Params = $this->known404->getParams();
            // Прверяем есть ли запрошенный url среди исключений
            $rules404List = array_filter(explode("\n", $known404Params['rules']['arr']['rulesExclude404']['value']));
            $matchesRules = self::matchesRules($rules404List, $this->url);
            if (empty($matchesRules)) {
                // Получаем данные о рассматриваемом url в справочнике "Ошибки 404"
                $par = array('url' => $this->url);
                $fields = array('table' => $error404Table);
                $rows = $db->select('SELECT * FROM &table WHERE url = :url LIMIT 1', $par, $fields);
                if (count($rows) == 0) {
                    // Добавляем запись в справочник
                    $dataList = $config->getStructureByName('Ideal_DataList');
                    $prevStructure = $dataList['ID'] . '-';
                    $par = array('structure' => 'Ideal_Error404');
                    $fields = array('table' => $config->db['prefix'] . 'ideal_structure_datalist');
                    $row = $db->select('SELECT ID FROM &table WHERE structure = :structure', $par, $fields);
                    $prevStructure .= $row[0]['ID'];
                    $params = array(
                        'prev_structure' => $prevStructure,
                        'date_create' => time(),
                        'url' => $this->url,
                        'count' => 1,
                    );
                    $db->insert($error404Table, $params);
                } elseif ($rows[0]['count'] < 15) {
                    $this->send404 = false;

                    // Увеличиваем счётчик посещения страницы
                    $values = array('count' => $rows[0]['count'] + 1);
                    $par = array('url' => $this->url);
                    $db->update($error404Table)->set($values)->where('url = :url', $par)->exec();
                } else {
                    $this->send404 = false;

                    // Переносим данные из справочника в файл с известными 404
                    $known404List = array_filter(explode("\n", $known404Params['known']['arr']['known404']['value']));
                    $known404List[] = $this->url;
                    $known404Params['known']['arr']['known404']['value'] = implode("\n", $known404List);
                    $this->known404->setParams($known404Params);
                    $this->known404->saveFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');
                    $par = array('url' => $this->url);
                    $db->delete($error404Table)->where('url = :url', $par)->exec();
                }
            }
        } elseif ($isAdmin) {
            // Если пользователь залогинен в админку, то удаляем запрошенный адрес из справочника "Ошибки 404"
            $par = array('url' => $this->url);
            $db->delete($error404Table)->where('url = :url', $par)->exec();
        }
    }

    /**
     * Фильтрует массив известных 404-ых или правил игнорирования по совпадению с запрошенным адресом
     *
     * @param array $rules Список правил с которыми сравнивается $url
     * @param string $url Запрошенный адрес
     * @return array Массив совпадений запрошенного адреса и извесных 404-ых
     */
    private function matchesRules($rules, $url)
    {
        return array_filter($rules, function ($rule) use ($url) {
            if (strpos($rule, '/') !== 0) {
                $rule = '/^' . addcslashes($rule, '/\\.') . '$/';
            }
            if (!empty($rule) && (preg_match($rule, $url))) {
                return true;
            }
            return false;
        });
    }
}
