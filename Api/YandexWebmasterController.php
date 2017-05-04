<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
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

    /** @var bool Признак необходимости переопределения типа отдаваемого контента */
    protected $jsonResponse = false;

    /**
     * Реакиця контроллера на запрос
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function setTokenAction(Router $router)
    {
        $request = new Request();
        $oldToken = $request->oldToken;
        $newToken = $request->newToken;

        // Проверяем правильность переданного токена для авторизации
        $config = Config::getInstance();
        if ($config->yandex['token'] != $oldToken) {
            // Старый токен не совпадает с переданным значением, отдаём 404
            $response = $this->error404Action();
            $router->is404 = true;
        } else {
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
        }
        return $response;
    }

    public function getHttpHeaders()
    {
        if ($this->jsonResponse) {
            return array('content-type' => 'application/json');
        } else {
            return parent::getHttpHeaders();
        }
    }
}
