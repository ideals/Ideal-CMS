<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Cache;

use Ideal\Core\FileCache;

/**
 * Класс для обработки реакции на изменения настроек файлового кэширования
 *
 */
class Model
{

    protected $configFileClass;

    /**
     * При инициализации модели сохраняем класс ConfigPhp в отдельную переменную
     *
     * @param \Ideal\Structure\Service\SiteData\ConfigPhp $configFileClass Экземпляр клаксса "ConfigPhp"
     */
    public function __construct($configFileClass)
    {
        $this->configFileClass = $configFileClass;
    }

    /**
     * Отвечает за реакции системы на изменения настроек файлового кэширования
     *
     * @return array Массив содержащий флаг успешности проверки настроек, а так же текст и набор классов в случае обнаружения ошибок
     */
    public function checkSettings()
    {
        $response = array('res' => true, 'text' => '', 'class' => '');
        $responseGV = $this->configFileClass->pickupValues();
        if ($responseGV['res'] === false) {
            $response['res'] = false;
            $response['text'] = $responseGV['text'];
            $response['class'] = 'alert alert-danger';
        } else {
            $params = $this->configFileClass->getParams();
            // Запускаем очистку кэша если он отключен
            if (!$params['cache']['arr']['fileCache']['value']) {
                FileCache::clearFileCache();
            } else {
                // Проверяем возможность записи в файл при включении кэширования
                $checkFileCacheResponse = FileCache::checkFileCache();
                if ($checkFileCacheResponse != 'ok') {
                    $response['res'] = false;
                    $response['text'] = $checkFileCacheResponse;
                    $response['class'] = 'alert alert-danger';
                }
            }

            //Перезаписываем данные в исключениях кэша
            $responseCEP = self::cacheExcludeProcessing($params['cache']['arr']['excludeFileCache']['value']);
            if ($responseCEP['res'] === false) {
                $response['res'] = false;
                $response['text'] = $responseCEP['text'];
                $response['class'] = 'alert alert-danger';
            }
        }

        return $response;
    }

    /**
     * Обрабатывает список исключений из настроек кэша
     *
     * @param string $string Значение поля "Адреса для исключения из кэша"
     *
     * @return array Массив содержащий флаг успешности проверки настроек, а так же текст в случае обнаружения ошибок
     */
    private static function cacheExcludeProcessing($string)
    {
        $response = array('res' => true);

        // Экранируем переводы строки для обработки каждой строки
        $string = str_replace("\r", '', $string);
        $lines = explode("\n", $string);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                // Пропускаем пустые линии в списке исключений из кэша
                continue;
            }
            if (!FileCache::addExcludeFileCache($line)) {
                $response['res'] = false;
                $response['text'] = 'Не получилось сохранить настройки исключений в файл';
            }
        }

        return $response;
    }
}
