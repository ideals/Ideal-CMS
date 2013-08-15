<?php
namespace Ideal\Field\Url;

use Ideal\Core\Config;
use Ideal\Core\PluginBroker;

class Model
{
    protected $fieldName; // TODO сделать возможность определять url Не только по полю url
    protected $parentUrl;

    public function __construct($fieldName = 'url')
    {
        // TODO доработать тут и в контроллере возможность указывать кастомное название поля url
        $this->fieldName = $fieldName;
    }


    public function setParentUrl($path)
    {
        // Обратиться к модели для получения своей части url, затем обратиться
        // к более старшим структурам пока не доберёмся до конца

        if (count($path) > 2 AND $path[1]['url'] == '/') {
            // Мы находимся внутри главной - в ней url не работают
            return '---';
        };

        // TODO если первая структура не стартовая, то нужно определить путь от стартовой структуры

        // Если первая структура в пути — стартовая структура, то просто объединяем url

        if (!isset($path[0]['url'])) {
            // Путь может быть не задан в случае установки parentUrl для главной странице
            $this->parentUrl = '';
            return '';
        }

        $url = $path[0]['url'];
        unset($path[0]);

        // Объединяем все участки пути
        foreach($path as $v) {
            if (strpos($v['url'], 'http:') === 0
                OR strpos($v['url'], '/') === 0) {
                // Если в одном из элементов пути есть ссылки на другие страницы, то путь построить нельзя
                return '---';
            }
            if (isset($v['is_skip']) AND $v['is_skip']) continue;
            $url .= '/' . $v['url'];
        }

        $this->parentUrl = $url;

        return $url;
    }


    public function getUrl($lastUrlPart)
    {
        return $this->getUrlWithPrefix($lastUrlPart, $this->parentUrl);
    }


    static function getUrlWithPrefix($lastPart, $parentUrl = '')
    {
        $lastUrlPart = $lastPart['url'];

        if ($parentUrl == '---') {
            // В случае, когда родительский url неопределён
            return '---';
        }

        $config = Config::getInstance();

        $structures = $config->structures;
        $startStructure = reset($structures);

        if ($lastUrlPart == '/' OR $lastUrlPart == $startStructure['url']) {
            // Ссылка на главную обрабатывается особым образом
            $parentUrl = $startStructure['url'];
            if ($parentUrl == '') {
                return '/';
            } else {
                return $parentUrl . '/index' . $config->urlSuffix;
            }
        }

        $pluginBroker = PluginBroker::getInstance();
        $arr = array('last' => $lastPart, 'parent' => $parentUrl);
        $arr = $pluginBroker->makeEvent('onGetUrl', $arr);
        $lastUrlPart = $arr['last']['url'];

        if (strpos($lastUrlPart, 'http:') === 0
            OR strpos($lastUrlPart, '/') === 0) {
            // Если это уже сформированная ссылка, её и возвращаем
            return $lastUrlPart;
        }

        if ($parentUrl == '' OR $parentUrl == '/') {
            $parentUrl = $startStructure['url'];
        } elseif (is_array($parentUrl)) {
            $parentUrl = implode('/', $parentUrl);
        }

        $url = $parentUrl;

        // Если URL предка нельзя составить
        if ($url == '---') return '---';

        $url .= '/';

        // Добавляем дочерний url
        if ($url != $lastUrlPart) {
            // Сработает только для всех ссылок, кроме главной '/'
            $url .= $lastUrlPart . $config->urlSuffix;
        }

        return $url;
    }


    /**
     * Удаление символов, неприменимых в URL
     * @param string $nm исходная ссылка
     * @return string преобразованная ссылка
     */
    static function translitUrl($nm)
    {
        $nm = Model::translit($nm);
        $nm = strtolower($nm);
        $arr = array('*' => '',
            '(' => '',
            ')' => '',
            '/' => '-',
            '«' => '',
            '»' => '',
            '.' => '',
            '№' => 'N',
            '"' => '',
            "'" => '',
            '?' => '',
            ' ' => '-',
            '&' => '',
            ',' => ''
        );
        $nm = strtr($nm, $arr);
        return $nm;
    }


    /**
     * Транслитерация файлов, с оставлением расширения неизменным
     * @param string $nm - исходное название файла
     * @return string преобразованное название файла
     */
    static function translit_file($nm)
    {
        $ext = '';
        $posDot = mb_strrpos($nm, '.');
        if ($posDot != 0) {
            $name = mb_substr($nm, 0, $posDot);
            $ext = '.' . mb_substr($nm, $posDot + 1);
        }
        $nm = Model::translit($name);
        $nm = strtolower($nm);
        $arr = array('*' => '',
            '(' => '',
            ')' => '',
            '/' => '-',
            '«' => '',
            '»' => '',
            '.' => '',
            '№' => 'N',
            '"' => '',
            '\'' => '',
            '?' => '',
            ' ' => '-',
            '&' => '',
            ',' => ''
        );
        $nm = strtr($nm, $arr);
        return $nm . $ext;
    }


    /**
     * Транслитерация русских букв в латинские
     * @param string $nm - исходная строка
     * @return string преобразованная строка
     */
    static function translit($nm)
    {
        $arr = array('а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'e',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'j',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'shh',
            'ы' => 'y',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
            'ь' => '',
            'ъ' => '',
            'А' => 'a',
            'Б' => 'b',
            'В' => 'v',
            'Г' => 'g',
            'Д' => 'd',
            'Е' => 'e',
            'Ё' => 'e',
            'Ж' => 'zh',
            'З' => 'z',
            'И' => 'i',
            'Й' => 'j',
            'К' => 'k',
            'Л' => 'l',
            'М' => 'm',
            'Н' => 'n',
            'О' => 'o',
            'П' => 'p',
            'Р' => 'r',
            'С' => 's',
            'Т' => 't',
            'У' => 'u',
            'Ф' => 'f',
            'Х' => 'h',
            'Ц' => 'c',
            'Ч' => 'ch',
            'Ш' => 'sh',
            'Щ' => 'shh',
            'Ы' => 'y',
            'Э' => 'e',
            'Ю' => 'yu',
            'Я' => 'ya',
            'Ь' => '',
            'Ъ' => ''
        );
        $nm = strtr($nm, $arr);
        return $nm;
    }

}