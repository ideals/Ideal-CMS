<?php
namespace Ideal\Template\SiteMap;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;

class Model extends \Ideal\Core\Admin\Model
{

    public function getObject($parentModel)
    {
        $this->setObjectByprevStructure($this->prevStructure);

        // Считываем из БД все открытые разделы
        $list = $this->getList();

        // Строим текстовую переменную с ul-списком разделов
        $this->object['content'] .=  $this->createSiteMap($list);

        return $this->object;
    }


    public function getList()
    {
        $config = Config::getInstance();

        // Определение стартовой структуры и начать считываение с неё
        $structure = $config->structures[0];
        $className = Util::getClassName($structure['structure'], 'Structure') . '\\Site\\Model';
        /** @var $startStructure \Ideal\Structure\Part\Site\Model */
        $startStructure = new $className($structure['ID']);
        $elements = $startStructure->getStructureElements();

        $path = array($structure);
        $elements = $this->recursive($path, $elements);

        return $elements;
    }


    protected function recursive($path, $elements)
    {
        $end = end($path);
        $newElements = array();
        // Проходился по всем внутренним структурам и, если вложены другие структуры, получаем и их элементы
        foreach ($elements as $element) {
            $newElements[] = $element;
            if (!isset($element['structure']) OR ($element['structure'] == $end['structure'])) {
                continue;
            }
            // Если структуры не совпадают, считываем элементы вложенной структуры
            $className = Util::getClassName($element['structure'], 'Structure') . '\\Site\\Model';
            $prevStructure = $element['prev_structure'] . '-' . $element['ID'];
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
     * @param $list Древовидный список
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

            $str .= '<li><a href="' . $v['link'] . '">'
                  . $v['name'] . '</a>';
            $lvl = $v['lvl'];
        }
        $str .= "</li>\n</ul>\n";
        return $str;
    }


    public function setObjectNew()
    {

    }

}