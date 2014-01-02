<?php
namespace Ideal\Structure\Part\Admin;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;
use Ideal\Field\Cid;

class ModelAbstract extends \Ideal\Core\Admin\Model
{
    public $cid;

    protected function getWhere($where)
    {
        $path = $this->getPath();
        $c = count($path);
        $end = end($path);
        if ($c == 1 OR ($c > 1 AND $end['structure'] != $path[$c - 2]['structure'])) {
            // Считываем все элементы первого уровня
            $where .= " AND lvl=1";
        } else {
            // Считываем все элементы последнего уровня из пути
            $lvl = $end['lvl'] + 1;
            $cidModel = new Cid\Model($this->params['levels'], $this->params['digits']);
            $cid = $cidModel->getCidByLevel($end['cid'], $end['lvl'], false);
            $where .= "AND lvl={$lvl} AND cid LIKE '{$cid}%'";
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }
        return $where;
    }

    /**
     * Определение пути по ID элементов пути
     * @param array $path Начальная, уже найденная часть пути
     * @param $par
     * @return $this
     */
    public function detectPageByIds($path, $par)
    {
        /* @var Db $db */
        $db = Db::getInstance();

        if (0 == count($par)) {
            $this->path = $path;
            return $this;
        }

        $ids = implode(',', $par);
        $_sql = "SELECT * FROM {$this->_table}
                          WHERE ID IN ({$ids}) AND prev_structure='{$this->prevStructure}' ORDER BY cid";
        $result = $db->queryArray($_sql);

        // TODO обработка случая, когда ничего не нашлось — 404

        // Проверка найденных элементов из БД на соответствие последовательности ID в par
        // и последовательности cid адресов
        $cidModel = new Cid\Model($this->params['levels'], $this->params['digits']);
        $start = reset($result);
        $cidPrev = $cidModel->getBlock($start['cid'], $start['lvl'] - 1); // находим блок cid предыдущего уровня
        $trueResult = array();
        $parElement = reset($par);
        foreach ($result as $v) {
            if ($v['ID'] != $parElement) {
                // Если ID найденного элемента не сооветствует ID в переданной строке par
                continue;
            }
            $cidCurr = $cidModel->getBlock($v['cid'], $v['lvl'] - 1); // находим блок cid предыдущего уровня
            if ($cidPrev != $cidCurr) {
                // Если предыдущий блок cid не равен предыдущему блоку этого cid
                continue;
            }
            $trueResult[] = $v;
            $parElement = next($par);
            $cidPrev = $cidModel->getBlock($v['cid'], $v['lvl']); // запоминаем блок cid пройденного уровня
        }

        $par = array_slice($par, count($trueResult)); // отрезаем найденную часть пути от $par

        $this->path = array_merge($path, $trueResult);

        $config = Config::getInstance();
        if (0 != count($par)) {
            // Ещё остались неопределённые элементы пути. Запускаем вложенную структуру.
            $trueResult = $this->path;
            $end  = array_pop($trueResult);
            $prev = array_pop($trueResult);
            $structure = $config->getStructureByName($prev['structure']);
            $modelClassName = Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Model';
            /* @var $structure Model */
            $structure = new $modelClassName($structure['ID'] . '-' . $end['ID']);
            // Запускаем определение пути и активного объекта по $par
            $model = $structure->detectPageByIds($this->path, $par);
            return $model;
        }

        return $this;
    }


    /**
     * Считываем наибольший cid на уровне $lvl для родительского $cid
     * @param string $cid Родительский cid
     * @param int $lvl Уровень, на котором нужно получить макс. cid
     * @return string Максимальный cid на уровне $lvl
     */
    function getNewCid($cid, $lvl)
    {
        /* @var Db $db */
        $db = Db::getInstance();

        $cidModel = new Cid\Model($this->params['levels'], $this->params['digits']);
        $parentCid = $cidModel->getCidByLevel($cid, $lvl - 1, false);
        $_sql = "SELECT cid FROM {$this->_table} WHERE cid LIKE '{$parentCid}%' AND lvl={$lvl} ORDER BY cid DESC LIMIT 1";
        $cidArr = $db->queryArray($_sql);
        if (count($cidArr) > 0) {
            // Если элементы на этом уровне есть, берём cid последнего
            $cid = $cidArr[0]['cid'];
        } else {
            // Если элементов на этом уровне нет, берём id родителя
        }
        // Прибавляем единицу в cid на нашем уровне
        return $cidModel->setBlock($cid, $lvl, '+1', true);
    }


    /**
     * Инициализирует переменную $pageData данными по умолчанию для нового элемента
     */
    public function setPageDataNew()
    {
        parent::setPageDataNew();
        $path = $this->getPath();
        $c = count($path);
        $end = end($path);
        if ($c < 2 OR ($c > 1 AND $path[$c - 2]['structure'] != $end['structure'])) {
            $prevStructure = $this->prevStructure;
            $lvl = 1;
        } else {
            // Остаёмся в рамках того же модуля
            $lvl = $end['lvl'] + 1;
            $prevStructure = $end['prev_structure'];
        }
        $pageData['lvl'] = $lvl;
        $pageData['prev_structure'] = $prevStructure;
        $this->setPageData($pageData);
    }


    public function delete()
    {
        $lvl = $this->pageData['lvl'] + 1;
        $cid = new Cid\Model($this->params['levels'], $this->params['digits']);
        $cid = $cid->getCidByLevel($this->pageData['cid'], $this->pageData['lvl'], false);
        $_sql = "SELECT ID FROM {$this->_table} WHERE lvl={$lvl} AND cid LIKE '{$cid}%'";
        $db = Db::getInstance();
        $res = $db->queryArray($_sql);
        if (count($res) > 0) {
            return 2;
        }
        $db->delete($this->_table, $this->pageData['ID']);
        // TODO сделать проверку успешности удаления
        return 1;

    }
}