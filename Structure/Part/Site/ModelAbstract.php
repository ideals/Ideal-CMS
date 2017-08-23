<?php
namespace Ideal\Structure\Part\Site;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Site;
use Ideal\Core\Util;
use Ideal\Field;
use Ideal\Field\Url;
use Ideal\Structure\User;

class ModelAbstract extends Site\Model
{

    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        // составляем запрос из списка URL
        $_sql = ' is_skip=1';
        foreach ($url as $v) {
            if ($v == '') {
                continue;
            }
            $_sql .= ' OR BINARY url="' . $db->real_escape_string($v) . '"';
        }

        // Для авторизированных в админку пользователей отображать скрытые страницы
        $user = new User\Model();
        $checkActive = ($user->checkLogin()) ? '' : ' AND is_active=1';

        $_sql = "SELECT * FROM {$this->_table} WHERE ({$_sql})
                    AND prev_structure='{$this->prevStructure}' {$checkActive} ORDER BY lvl, cid";

        $list = $db->select($_sql); // запрос на получение всех страниц, соответствующих частям url

        // Страницу не нашли устанавливаем флаг 404-ошибки
        if (!isset($list[0]['cid'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        $cidModel = new Field\Cid\Model($this->params['levels'], $this->params['digits']);

        // Убираем ненужные элементы с теми же URL, но из других ветвей

        // Распределяем считанные cid'ы по веткам
        $branches = array();
        foreach ($list as $v) {
            if ($v['lvl'] == 1) {
                $cid = $v['cid'];
                $branches[$cid]['count'] = 1;
                $branches[$cid]['branch'][] = $v;
            } else {
                $cid = $cidModel->getCidByLevel($v['cid'], $v['lvl'] - 1);
                if (!isset($branches[$cid]['count'])) {
                    continue;
                }
                $newCid = $v['cid'];
                $branches[$newCid] = $branches[$cid];
                $branches[$newCid]['count']++;
                $branches[$newCid]['branch'][] = $v;
            }
        }

        // Сортируем ветки по количеству элементов (по убыванию), а при равном количестве — по is_skip

        usort(
            $branches,
            function ($a, $b) {
                $res = $b['count'] - $a['count'];
                if ($res == 0) {
                    // Количество элементов одинаковое, сортируем по is_skip (первыми без него)
                    $aEnd = end($a['branch']);
                    $bEnd = end($b['branch']);
                    $res = $aEnd['is_skip'] - $bEnd['is_skip'];
                }
                return $res;
            }
        );

        // Проходим каждую ветку, начиная с наибольшей, пока не найдём полный путь
        // без разрывов или не кончатся ветки
        $newPath = array();
        foreach ($branches as $branch) {
            $isOk = true;
            foreach ($branch['branch'] as $k => $v) {
                if (($k + 1) != $v['lvl']) {
                    $isOk = false;
                    break;
                }
            }
            // Если в анализируемой ветке найден разрыв — пропускаем её
            if (!$isOk) {
                continue;
            }

            // Проверяем, собирается ли нужный url из найденного пути
            $count = $this->checkDetectedUrlCount($url, $branch['branch']);
            if ($count == 0) {
                continue;
            }

            $end = end($branch['branch']);
            if ($end['is_skip'] == 1) {
                $endUrl = end($url);
                if ($endUrl == $end['url']) {
                    // Если в URL запрошен элемент с is_skip=1
                    $newPath = $branch['branch'];
                    unset($url[(count($url)-1)]);
                    break;
                }

                // Проверка случая, если за найденным элементом с is_skip есть ещё элементы с is_skip
                $c = count($branch['branch']) - 2; // начинаем обход с предпоследнего элемента
                $notCorrectBranch = false;
                for ($i = $c; $i >= 0; $i--) {
                    $element = $branch['branch'][$i];
                    if (($element['url'] == $endUrl) && ($element['is_skip'] == 1)) {
                        $notCorrectBranch = true;
                    }
                }
                if ($notCorrectBranch) {
                    continue;
                }

                // Если последний элемент в максимальной цепочке — пропущенный, проверяем не хранится
                // ли в нём другая структура
                $structureName = $this->getStructureName();
                if ($end['structure'] != $structureName) {
                    // Получаем модель вложенной структуры
                    $structure = $this->getNestedStructure($end);
                    // Запускаем определение пути и активной модели по $par
                    $newPath = array_merge($path, $branch['branch']);
                    $oldPath = $path;
                    $path = array(); // сбрасываем начальный путь, т.к. влили его в основной newPath

                    // Подсчитываем кол-во элементов пути без is_skip
                    $count = 0;
                    foreach ($newPath as $v) {
                        if (!isset($v['is_skip']) || $v['is_skip'] == 0) {
                            $count++;
                        }
                    }
                    $count = ($count < 2) ? 2 : $count;

                    // Уменьшаем наш url на кол-во найденных элементов без is_skip, за исключением первого
                    $nestedUrl = array_slice($url, $count - 2);

                    // Ищем остаток url во вложенной структуре
                    $model = $structure->detectPageByUrl($newPath, $nestedUrl);

                    if ($model->is404) {
                        // Если во вложенной структуре ничего не нашлось, перебираем ветки дальше
                        $path = $oldPath;
                        $newPath = array();
                        continue;
                    }
                    return $model;
                }
                continue;
            }
            $newPath = $branch['branch'];
            break;
        }

        if (count($newPath) == 0) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        $this->path = array_merge($path, $newPath);

        // Определяем количество совпадений сегментов найденного пути и запрошенного url
        $count = $this->checkDetectedUrlCount($url, $newPath);

        if ($count == 0) {
            // Не нашлось никаких совпадений запрашиваемого url с наиболее подходящей найденной веткой
            $this->is404 = true;
            return $this;
        }

        $url = array_slice($url, $count);
        if (count($url) > 0) {
            // Остались неразобранные сегменты URL, запускаем вложенную структуру
            // Определяем оставшиеся элементы пути
            $end = end($this->path);
            // Получаем модель вложенной структуры
            $structure = $this->getNestedStructure($end);
            if (is_null($structure)) {
                // Ксли вложенная структура такая же, то это значит что 404 ошибка
                $this->is404 = true;
            } else {
                // Запускаем определение пути и активной модели по $par
                $model = $structure->detectPageByUrl($this->path, $url);
                return $model;
            }
        }
        // Неразобранных сегментов не осталось, возвращаем в качестве модели сам объект
        return $this;
    }

    /**
     * Определяет количество совпадающих сегментов найденного пути и запрошенного url
     *
     * @param array $url     Массив сегментов url для определения пути
     * @param array $newPath Массив найденных элементов пути из БД
     * @return int Количество совпадающих сегментов найденного пути и запрошенного url
     */
    protected function checkDetectedUrlCount($url, $newPath)
    {
        // В случае, если новый путь состоит из одного элемента, который пропускается
        if (count($newPath) == 1 && isset($newPath[0]['is_skip']) && $newPath[0]['is_skip'] == 1) {
            return 1;
        }

        // Подсчитываем кол-во элементов пути, без учёта пропущенных сегментов
        // и составляем строку найденной части URL
        $count = 0;
        $parsedUrl = $sep = '';
        foreach ($newPath as $v) {
            if (!isset($v['is_skip']) || ($v['is_skip'] == 0)) {
                $parsedUrl .= $sep . $v['url'];
                $sep = '/';
                $count++;
            }
        }

        // Вырезаем из переданного URL найденное количество сегментов и склеиваем их в строку
        $parsedUrlPart = array_slice($url, 0, $count);
        $parsedUrlPart = implode('/', $parsedUrlPart);
        if ($parsedUrl != $parsedUrlPart) {
            $count = 0;
        } elseif ($parsedUrl == '' && $parsedUrlPart == '') {
            // Если весь путь состоит из пропущенных элементов (is_skip=1)
            $count = 1;
        }

        return $count;
    }

    /**
     * Определение вложенной структуры по $end['structure']
     *
     * @param array $end Все параметры родительской структуры
     * @return Model Инициализированный объект модели вложенной структуры
     */
    protected function getNestedStructure($end)
    {
        $config = Config::getInstance();
        $rootStructure = $config->getStructureByClass(get_class($this));
        $modelClassName = Util::getClassName($end['structure'], 'Structure') . '\\Site\\Model';

        if (get_class($this) == trim($modelClassName, '\\')) {
            // todo Если вложена такая же структура, то надо продолжать разбор url, но не здесь
            return null;
        }

        /* @var $structure Model */
        $structure = new $modelClassName($rootStructure['ID'] . '-' . $end['ID']);

        return $structure;
    }

    /**
     * @param int $page Номер отображаемой страницы
     * @return array Полученный список элементов
     */
    public function getList($page = null)
    {
        if ($this->pageData['is_self_menu']) {
            return array();
        }

        $list = parent::getList($page);

        // Построение правильных URL
        $url = new Field\Url\Model();
        $url->setParentUrl($this->path);
        if (is_array($list) and count($list) != 0) {
            foreach ($list as $k => $v) {
                $list[$k]['link'] = $url->getUrl($v);
            }
        }

        return $list;
    }

    /**
     * Построение пути в рамках одной структуры.
     */
    public function getLocalPath()
    {
        $category = $this->pageData;

        if ($category['lvl'] == 1) {
            // Если в локальной структуре родителей нет, возвращаем сам объект
            return array($category);
        }

        // По cid определяем cid'ы всех родителей
        $cid = new \Ideal\Field\Cid\Model($this->params['levels'], $this->params['digits']);
        $cids = $cid->getParents($category['cid']);

        $path = array();
        if (count($cids) > 0) {
            // Выстраиваем строку cid'ов для запроса в БД
            $strCids = $separator = '';
            foreach ($cids as $v) {
                $strCids .= $separator . "'" . $v . "'";
                $separator = ', ';
            }

            // Считываем все элементы с указанными cid'ами
            $db = Db::getInstance();
            $_sql = "SELECT * FROM {$this->_table} WHERE cid IN ({$strCids}) ORDER BY cid";
            $path = $db->select($_sql);
        }

        $path = array_merge($path, array($category)); // добавляем наш элемент к родительским

        return $path;
    }

    public function getStructureElements()
    {
        $db = Db::getInstance();
        $config = Config::getInstance();
        $urlModel = new Url\Model();

        $_sql = "SELECT * FROM {$this->_table} ORDER BY cid";
        $list = $db->select($_sql);

        if (count($this->path) == 0) {
            $url = array('0' => array('url' => $config->structures[0]['url']));
        } else {
            $url = $this->path;
        }

        $lvl = 0;
        $lvlExit = false;
        foreach ($list as $k => $v) {
            if ($v['is_active'] == 0) {
                // Пропускаем неактивный элемент и ставим флаг для пропуска вложенных элементов
                $lvlExit = $v['lvl'];
                unset($list[$k]);
                continue;
            }
            if ($lvlExit !== false && $v['lvl'] > $lvlExit) {
                // Если это элемент, вложенный в скрытый, то не включаем его в карту сайта
                unset($list[$k]);
                continue;
            }
            $lvlExit = false;
            if ($v['lvl'] > $lvl) {
                if (($v['url'] != '/') && ($k > 0)) {
                    $url[] = $list[$k - 1];
                }
                $urlModel->setParentUrl($url);
            } elseif ($v['lvl'] < $lvl) {
                // Если двойной или тройной выход добавляем соответствующий мультипликатор
                $c = $lvl - $v['lvl'];
                $url = array_slice($url, 0, -$c);
                $urlModel->setParentUrl($url);
            }
            $lvl = $v['lvl'];
            $list[$k]['link'] = $urlModel->getUrl($v);
        }
        return $list;
    }

    /**
     * @param $where
     * @return string
     */
    protected function getWhere($where)
    {
        // Считываем все элементы первого уровня
        $lvl = 1;
        $cid = '';

        if (count($this->path) > 0) {
            // Считываем все элементы последнего уровня из пути
            $c = count($this->path);
            $end = end($this->path);
            if (isset($this->path[$c - 2]) && ($end['structure'] == $this->path[$c - 2]['structure'])) {
                $lvl = $end['lvl'] + 1;
                $cidModel = new Field\Cid\Model($this->params['levels'], $this->params['digits']);
                $cid = $cidModel->getCidByLevel($end['cid'], $end['lvl'], false);
                $cid = " AND cid LIKE '{$cid}%'";
            }
        }

        if (is_array($end) && $end['is_self_menu'] == 0) {
            $where .= "AND lvl={$lvl} {$cid} AND is_active=1 AND is_not_menu=0";
        }

        if ($where != '') {
            $where = 'WHERE ' . $where;
        }

        return $where;
    }
}
