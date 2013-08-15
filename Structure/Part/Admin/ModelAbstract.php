<?php
namespace Ideal\Structure\Part\Admin;

use Ideal\Core\Db;
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


    public function detectPageByIds($par)
    {
        /* @var Db $db */
        $db = Db::getInstance();

        if (count($par) == 0) {
            $trueResult = array();
        } else {
            $ids = implode(',', $par);
            $_sql = "SELECT * FROM {$this->_table}
                              WHERE ID IN ({$ids}) AND structure_path='{$this->structurePath}' ORDER BY cid";
            $result = $db->queryArray($_sql);

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

            $par = array_slice($par, count($trueResult));
        }

        $this->path = $trueResult;
        return $par;
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
     * Инициализирует переменную $this->object данными по умолчанию для нового элемента
     */
    public function setObjectNew()
    {
        $path = $this->getPath();
        $c = count($path);
        $end = end($path);
        if ($c < 2 OR ($c > 1 AND $path[$c - 2]['structure'] != $end['structure'])) {
            $structurePath = $this->structurePath;
            $lvl = 1;
        } else {
            // Остаёмся в рамках того же модуля
            $lvl = $end['lvl'] + 1;
            $structurePath = $end['structure_path'];
        }

        $this->object = array(
            'lvl' => $lvl,
            'structure_path' => $structurePath
        );

    }


    public function delete()
    {
        $lvl = $this->object['lvl'] + 1;
        $cid = new Cid\Model($this->params['levels'], $this->params['digits']);
        $cid = $cid->getCidByLevel($this->object['cid'], $this->object['lvl'], false);
        $_sql = "SELECT ID FROM {$this->_table} WHERE lvl={$lvl} AND cid LIKE '{$cid}%'";
        $db = Db::getInstance();
        $res = $db->queryArray($_sql);
        if (count($res) > 0) {
            return 2;
        }
        $db->delete($this->_table, $this->object['ID']);
        // TODO сделать проверку успешности удаления
        return 1;

    }
}