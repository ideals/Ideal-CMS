<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Addon\YandexWebmaster;

use YandexWebmasterAPI\WebmasterApi;
use Ideal\Core\Config;

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
            // Если токена нет, то получаем из настроек идентификатор приложения
            // и предлагаем пользователю обновить токен
            $clientId = $config->yandex['clientId'];
            if ($clientId) {
                // Получаем имя пользователя или электронный адрес владельца приложения из настроек
                $loginHintAdd = '';
                $loginHint = $config->yandex['loginHint'];
                if ($loginHint) {
                    $loginHintAdd = '&login_hint=' . $loginHint;
                }
                $response = array('update_token' => 'https://oauth.yandex.ru/authorize?response_type=token&client_id=' . $clientId . $loginHintAdd);
            } else {
                // Если нет идентификатора приложения предлагаем пользователю создать приложение
                $response = array('create_app' => 'https://oauth.yandex.ru/client/new');
            }
        }

        // Если достаточно данных, то пытваемся отправить оригинальный текст
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

            // Если удалось получить дентификатор хоста, то пытаемсяотправить оригинальный текст
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

        return json_encode($response, JSON_FORCE_OBJECT);
    }
}
