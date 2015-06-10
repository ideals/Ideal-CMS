<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\SiteMap;

use Ideal\Core\Config;
use Ideal\Core\Util;

/**
 * Класс построения html-карты сайта на основании структуры БД
 *
 */
class Model extends \Ideal\Addon\AbstractModel
{

    /** @var array Массив правил для запрещения отображения ссылок в карте сайта */
    protected $disallow = array();

    /**
     * Извлечение настроек карты сайта из своей таблицы,
     * построение карты сайта и преобразование её в html-формат
     *
     * @return array
     */
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $this->pageData['disallow'] = str_replace("\r\n", "\n", $this->pageData['disallow']);
        $this->disallow = explode("\n", $this->pageData['disallow']);

        $mode = explode('\\', get_class($this->parentModel));
        if ($mode[3] == 'Site') {
            // Для фронтенда к контенту добавляется карта сайта в виде ul-списка разделов
            $list = $this->getList(1); // считываем из БД все открытые разделы
            $this->pageData['content'] .= $this->createSiteMap($list); // строим html-код карты сайта
        }

        return $this->pageData;
    }

    /**
     * Построение карты сайта в виде дерева
     *
     * @param int $page Не используется
     * @return array
     */
    public function getList($page = null)
    {
        $config = Config::getInstance();

        // Определение стартовой структуры и начать считывание с неё
        $structure = $config->structures[0];
        $className = Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';
        /** @var $startStructure \Ideal\Structure\Part\Site\Model */
        $startStructure = new $className($structure['ID']);
        $elements = $startStructure->getStructureElements();

        $path = array($structure);
        $elements = $this->recursive($path, $elements);

        return $elements;
    }

    /**
     * Рекурсивный метод построения дерева карты сайта
     *
     * @param $path
     * @param $elements
     * @return array
     */
    protected function recursive($path, $elements)
    {
        $config = Config::getInstance();
        $end = end($path);
        $newElements = array();
        // Проходился по всем внутренним структурам и, если вложены другие структуры, получаем и их элементы
        foreach ($elements as $element) {
            $newElements[] = $element;
            if (!isset($element['structure']) || ($element['structure'] == $end['structure'])) {
                continue;
            }
            // Если структуры предпоследнего $end и последнего $element элементов не совпадают,
            // считываем элементы вложенной структуры
            $structure = $config->getStructureByName($end['structure']);
            $className = Util::getClassName($element['structure'], 'Structure') . '\\Site\\Model';
            $prevStructure = $structure['ID'] . '-' . $element['ID'];
            $nextStructure = new $className($prevStructure);
            $fullPath = array_merge($path, array($element));
            $nextStructure->setPath($fullPath);
            // Считываем элементы из вложенной структуры
            $addElements = $nextStructure->getStructureElements();
            // Рекурсивно читаем вложенные элементы из вложенной структуры
            $addElements = $this->recursive($fullPath, $addElements);

            // Увеличиваем уровень вложенности на считанных элементах
            foreach ($addElements as $k => $v) {
                if (isset($v['lvl'])) {
                    $addElements[$k]['lvl'] += $element['lvl'];
                } else {
                    $addElements[$k]['lvl'] = $element['lvl'] + 1;
                }
            }

            // Получившийся список добавляем в наш массив новых элементов
            $newElements = array_merge($newElements, $addElements);
        }
        return $newElements;
    }

    /**
     * Построение html-карты сайта на основе древовидного списка
     *
     * @param array $list Древовидный список
     * @return string html-код списка ссылок карты сайта
     */
    public function createSiteMap($list)
    {
        $str = '';
        $lvl = 0;
        foreach ($list as $k => $v) {
            if ($v['lvl'] > $lvl) {
                $str .= "\n<ul>\n";
            } elseif ($v['lvl'] == $lvl) {
                $str .= "</li>\n";
            } elseif ($v['lvl'] < $lvl) {
                // Если двойной или тройной выход добавляем соответствующий мультипликатор
                $c = $lvl - $v['lvl'];
                $str .= str_repeat("</li>\n</ul>\n</li>\n", $c);
            }

            if ((!isset($v['link'])) || (isset($v['is_skip']) && $v['is_skip'] == 1)) {
                $str .= '<li>' . $v['name'];
            } else {
                // Проходимся по массиву регулярных выражений. Если array_reduce вернёт саму ссылку,
                // то подходящего правила в disallow не нашлось и можно эту ссылку добавлять в карту сайта
                $tmp = $this->disallow;

                $link = array_reduce($tmp, function (&$res, $rule) {
                    if (!empty($rule)) {
                        if ($res == 1 || preg_match($rule, $res)) {
                            return 1;
                        }
                    }
                    return $res;
                }, $v['link']);
                if ($v['link'] !== $link) {
                    // Сработало одно из регулярных выражений, значит ссылку нужно исключить
                    continue;
                }
                if (empty($v['link'])) {
                    continue;
                }
                $str .= '<li><a href="' . $v['link'] . '">' . $v['name'] . '</a>';
            }
            $lvl = $v['lvl'];
        }
        $str .= "</li>\n</ul>\n";
        return $str;
    }
}
