<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Url;

use Ideal\Field\AbstractController;
use Ideal\Core\Site;
use Ideal\Core\Config;

/**
 * Поле, содержащее финальный сегмент URL для редактируемого элемента
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'url' => array(
 *         'label' => 'URL',
 *         'sql'   => 'varchar(255) not null',
 *         'type'  => 'Ideal_Url'
 *     ),
 */
class Controller extends AbstractController
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $url = new Model();
        $value = array('url' => htmlspecialchars($this->getValue()));
        $link = $url->getUrlWithPrefix($value, $this->model->getParentUrl());
        $link = $url->cutSuffix($link);
        // Проверяем, является ли url этого объекта частью пути
        $addOn = '';
        if (($link[0] === '/') && ($value !== $link)) {
            // Выделяем из ссылки путь до этого объекта и выводим его перед полем input
            $path = substr($link, 0, strrpos($link, '/'));
            $addOn = '<span class="input-group-addon">' . $path . '/</span>';
        }
        return
            '<div class="input-group">' . $addOn
            . '<input type="text" class="form-control" name="' . $this->htmlName . '" id="' . $this->htmlName
            . '" value="' . $value['url'] . '">'
            . '</div>';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        $url = new Model($fieldName);
        $link = $url->getUrlWithPrefix($values, $this->model->getParentUrl());
        if ($link == '---') {
            // Если это страница внутри главной, то просто возвращаем поле url
            $link = $values[$fieldName];
        } else {
            // Если это не страница внутри Главной, то делаем ссылку
            $link = '<a href="' . $link . '" target="_blank">' . $link . '</a>';
        }
        return $link;
    }

    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // Если редактируется материал и ссылка не изменилась, ошибок нет
        if (!$isCreate && $this->getValue() == $this->newValue) {
            return $item;
        }

        // Если создается новый материал или изменилась ссылка при редактировании,
        // проверяем нет используется ли уже такой URL

        // Получаем SEO ссылку на создаваемый/редактируемый материал
        $url = new Model();
        $value = htmlspecialchars($this->newValue);
        $link = $url->getUrlWithPrefix(array('url' => $value), $this->model->getParentUrl());

        if (empty($value)) {
            $item['message'] = 'Поле url должно быть заполнено!';
            return $item;
        }

        if ($value[0] == '/' || parse_url($link, PHP_URL_SCHEME) != '') {
            // Если введённый url фактически является ссылкой, а не реальным URL,
            // то проверять его существование не надо
            return $item;
        }

        // Проверяем url на существование
        $httpCode = self::checkUrl($link);
        if ($httpCode == 200) {
            $item['message'] = 'URL: ' . $link . ' уже используется!';
        } elseif ($httpCode != 404) {
            // Если не 404 ошибка, то уведомляем об этом польлзователя и не создаём страницу
            $item['message'] = 'URL: ' . $link . ' выдаёт ошибку с HTTP-кодом ' . $httpCode;
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        // В url не нужны пробелы ни спереди, ни сзади
        $value = trim(parent::pickupNewValue());
        return $value;
    }

    /**
     * Проверяет url на существование
     * TODO проверка должна учитывать залогиненого пользователя
     *
     * @param string $url SEO ссылка на создаваемый/редактируемый материал
     * @return mixed HTTP-код ответа сервера
     */
    private static function checkUrl($url)
    {
        // Выстраиваем ссылку к создаваемой странице
        $config = Config::getInstance();
        $protocol = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
        $url = $protocol . $_SERVER['HTTP_HOST'] . $config->cms['startUrl'] . $url;

        // Инициализируем curl
        $ch = curl_init();
        curl_setopt(
            $ch,
            CURLOPT_USERAGENT,
            "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4"
        );

        // Устанавливаем значение url для проверки
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, 64, false); // CURLOPT_SSL_VERIFYPEER
        curl_setopt($ch, 81, 0); // CURLOPT_SSL_VERIFYHOST
        curl_exec($ch);

        // Получаем HTTP код
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return $httpCode;
    }
}
