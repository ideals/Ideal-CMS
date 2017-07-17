<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Addon\YandexWebmaster;

use Ideal\Structure\Service\SiteData\ConfigPhp;
use YandexWebmasterAPI\WebmasterApi;
use Ideal\Core\Config;
use Ideal\Core\Util;

/**
 * Реакция на действия из вкладки аддона "ЯндексВебмастер"
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{
    /**
     * Отправка оригинального текста в Яндекс.Вебмастер
     * @return mixed Ответ содержащий информацию
     */
    public function addOriginalTextAction()
    {
        $response = array('message' => 'Произошёл сбой требующий детального рассмотрения. Обратитесь к разработчикам');
        $config = Config::getInstance();

        // Получаем токен из настроек
        $token = $config->yandex['token'];
        if (!$token) {
            $ywmConfigPath =  '/' . $config->cmsFolder . '/index.php?par=4-Ideal_SiteData#yandex';
            $response = array('to_config' => $ywmConfigPath);
        } else {
            // Если достаточно данных, то пытаемся отправить оригинальный текст
            $wmApi = WebmasterApi::initApi($token);
            if (isset($wmApi->error_message)) {
                $response = array('message' => $wmApi->error_message);
            } else {
                // Проверяем добавлен ли текущий хост к аккаунту, под управлением которого создано приложение
                $hostId = '';
                $currentHost = $config->domain;
                $res = $wmApi->getHosts();
                foreach ($res->hosts as $host) {
                    $parsedUrl = parse_url($host->unicode_host_url);
                    if (strpos($parsedUrl['host'], $currentHost) !== false) {
                        $hostId = $host->host_id;
                        break;
                    }
                }

                // Если удалось получить идентификатор хоста, то пытаемся отправить оригинальный текст
                if ($hostId) {
                    $postOriginalTextRes = $wmApi->addOriginalText($hostId, $_POST['text']);
                    if (!isset($postOriginalTextRes->error_code) && isset($postOriginalTextRes->text_id)) {
                        $response = array('message' => 'Оригинальный текст успешно добавлен');
                    } else {
                        $response = array('message' => $postOriginalTextRes->error_message);
                    }
                } else {
                    $response = array('message' => 'Владелец приложения не имеет прав на управление данным сайтом');
                }
            }
        }

        return json_encode($response, JSON_FORCE_OBJECT);
    }

    public function updateTokenAction()
    {
        $response = array('message' => 'Произошёл сбой требующий детального рассмотрения. Обратитесь к разработчикам');
        $config = Config::getInstance();

        // Получаем токен из настроек
        $token = $config->yandex['token'];
        // Если токена нет, то получаем из настроек идентификатор приложения
        // и предлагаем пользователю обновить токен
        $clientId = $config->yandex['clientId'];

        if ($clientId) {
            // Адрес для запроса обновления токена
            $updateTokenUrl = 'https://oauth.yandex.ru/authorize?response_type=token&client_id=' . $clientId;

            // Получаем электронный адрес или имя пользователя,
            // которому нужно будет предоставить доступа для приложения
            $loginHint = $config->yandex['loginHint'];
            if ($loginHint) {
                $updateTokenUrl .= '&login_hint=' . $loginHint;
            }

            // Если нет токена, то генерируем произвольный для дальнейшей связи
            if (!$token) {
                $token = 'start-' . Util::randomChar(39);

                // Сохраняем токен
                $configSD = new ConfigPhp();
                $configSD->loadFile($config->cmsFolder . '/site_data.php');
                $params = $configSD->getParams();
                $params['yandex']['arr']['token']['value'] = $token;
                $configSD->setParams($params);
                $configSD->saveFile($config->cmsFolder . '/site_data.php');
            }

            // Дополняем запрос адресом сайта и старым токеном
            $stateString = json_encode(
                array(
                    'domain' => $config->domain,
                    'token' => $token,
                    'return' => $config->domain . '/' . $config->cmsFolder . '/index.php?par=4-Ideal_SiteData',
                    'returnHash' => 'yandex'
                )
            );
            $updateTokenUrl .= '&state=' . $stateString;

            $response = array('update_token' => $updateTokenUrl);
        } else {
            // Если нет идентификатора приложения предлагаем пользователю создать приложение
            $response = array('create_app' => 'https://oauth.yandex.ru/client/new');
        }
        return json_encode($response, JSON_FORCE_OBJECT);
    }
}
