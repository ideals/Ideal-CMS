<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\YandexWebmasterText;

use Ideal\Field\AbstractController;
use Ideal\Core\Config;
use YandexWebmasterAPI\WebmasterApi;

/**
 * Поле отправки текста в сервис "Яндекс.Вебмастер"
 *
 * Поле представляет из себя область для ввода текста и кнопку для отправки даных в сервис "Яндекс.Вебмастер.
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'yandexwebmastertext' => array(
 *         'label' => 'Текст для отправки в Яндекс.Вебмастер',
 *         'sql'   => 'mediumtext',
 *         'type'  => 'Ideal_YandexWebmasterText'
 *     ),
 */
class Controller extends AbstractController
{
    /** {@inheritdoc} */
    protected static $instance;

    /** @var  \Ideal\Addon\YandexWebmaster\Model Модель данных, в которой находится редактируемое поле */
    protected $model;

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $value = htmlspecialchars($this->getValue());

        $button = '';
        $config = Config::getinstance();
        // Получаем токен из настроек
        $token = $config->yandex['token'];

        // Получаем из настроек идентификатор приложения
        $clientId = $config->yandex['clientId'];

        // Формируем путь до страницы настроек связи с сервисом "Яндекс.Вебмастер"
        $ywmConfigPath =  '/' . $config->cmsFolder . '/index.php?par=4-Ideal_SiteData#yandex';

        // Если отсутствует токен или илентификатор приложения, то уведомляепм об этом пользователя
        // и предлагаем перейти на страницу настроек
        if (!$token || !$clientId) {
            $button .= <<<BUTTON
                <div class="has-error">
                    <span class="help-block">Не настроена связь с сервисом "Яндекс.Вебмастер".</span>
                </div>
                <span class="input-group-btn">
                    <button class="btn" onclick="ywmservice('{$ywmConfigPath}'); return false;">
                        Перейти на страницу настроек?
                    </button>
                </span>
BUTTON;
        } else {
            $wmApi = WebmasterApi::initApi($token);
            if (isset($wmApi->error_message)) {
                $button .= <<<BUTTON
                <div class="has-error">
                    <span class="help-block">Токен для связи с сервисом "Яндекс.Вебмастер" не актуален.</span>                
                </div>
                <span class="input-group-btn">
                    <button class="btn" onclick="ywmservice('{$ywmConfigPath}'); return false;">
                        Перейти на страницу настроек?
                    </button>
                </span>
BUTTON;
                $button .= '';
            } else {
                $button .= <<<BUTTON
                <span class="input-group-btn">
                    <button class="btn" onclick="sendYWT('#yw{$this->htmlName}'); return false;">
                        Отправить текст в Яндекс.Вебмастер
                    </button>
                </span>
BUTTON;
            }
        }

        $html = <<<HTML
            <div id="{$this->htmlName}-control-group">{$this->getLabelText()}<br />
            <textarea name="{$this->htmlName}" id="yw{$this->htmlName}" class="form-control">{$value}</textarea>
            <div class="text-center" style="margin-top: 10px;">
                    {$button}
                </div>
            </div>            
HTML;
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        return '';
    }

    public function getValue()
    {
        $value = parent::getValue();
        if (!$value) {
            // Получаем данные из полей
            $value = $this->model->getContentFromFields();
        }
        // Ставим перед открывающимися тегами переноса
        $value = str_replace('<p', ' <p', $value);
        $value = str_replace('<br', ' <br', $value);
        $value = strip_tags($value);
        return $value;
    }
}
