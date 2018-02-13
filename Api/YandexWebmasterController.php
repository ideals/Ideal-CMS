<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * Вызов метода API осуществляется путём http-запроса:
 * http://example.com/api/YandexWebmaster?action=setToken&oldToken=XXX&newToken=YYY
 * где
 * YandexWebmaster — имя вызываемого контроллера
 * setToken — имя вызываемого экшена контроллера
 * oldToken — старый токен Яндекс.Вебмастер (сохранённый в Ideal CMS)
 * newToken — новый токен, полученный от Яндекс.Вебмастера
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Api;

use Ideal\Core\Api\Controller;
use Ideal\Core\Api\Router;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\Service\SiteData\ConfigPhp;

class YandexWebmasterController extends Controller
{
    /**
     * Обязательный метод проверки авторизации API-запроса
     * Неавторизированные запросы всегда отдают 404-ую страницу
     *
     * @param Router $router
     * @return bool
     */
    public function authorize(Router $router)
    {
        $request = new Request();
        $oldToken = $request->oldToken;

        // Проверяем правильность переданного токена для авторизации
        $config = Config::getInstance();

        return $oldToken != '' && $config->yandex['token'] == $oldToken;
    }

    /**
     * Реакиця контроллера на запрос
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function setTokenAction(Router $router)
    {
        $request = new Request();
        $newToken = $request->newToken;

        // Проверяем правильность переданного токена для авторизации
        $config = Config::getInstance();
        // Сохраняем новый токен и уведомляем скрипт об успехе
        $configSD = new ConfigPhp();

        $file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
        $configSD->loadFile($file);
        $params = $configSD->getParams();
        $params['yandex']['arr']['token'] = array(
            'label' => 'Токен для авторизации в сервисе "Яндекс.Вебмастер"',
            'value' => $newToken,
            'type' => 'Ideal_Text'
        );
        $configSD->setParams($params);
        $configSD->saveFile($file);
        $response = json_encode(array('success' => true), JSON_FORCE_OBJECT);
        $this->jsonResponse = true;

        return $response;
    }
}
