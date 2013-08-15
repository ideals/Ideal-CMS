<?php
namespace Ideal\Core;

use Ideal\Core\Config;
use Ideal\Core\Util;

/**
 * Класс для работы с БД
 *
 */
class Db
{
    protected $dbName; // название базы, с которой идёт работа
    protected $lastError; // сообщение о последней ошибке

    private static $instance;
    
    private function __construct($dbName = '', $host = '', $login = '', $pass = '')
    {
        $config = Config::getInstance();
        if ($dbName == '' || $host == '' || $login == '' || $pass == '') {
            $dbName = $config->db['name'];
            $host   = $config->db['host'];
            $login  = $config->db['login'];
            $pass   = $config->db['password'];
        }
        // Подключаемся к БД
        $dbc = mysql_connect($host, $login, $pass);

        if ($dbc === false ) {
            Util::addError("Не могу подключиться к БД с параметрами: {$host}, {$login}");
            return false;
        }

        $res = mysql_select_db($dbName);
        if ($res === false) {
            Util::addError("Не могу подключиться к базе: {$dbName}");
            return false;
        }

        $this->dbName = $dbName;

        $db_charset = $config->db['charset'];
        if ($db_charset == 'WINDOWS-1251') {
            $db_charset = 'cp1251';
        }
        if ($db_charset == 'UTF-8') {
            $db_charset = 'utf8';
        }

        $this->query('set character set ' . $db_charset );

        $this->query('set names ' . $db_charset );

        return $res;
    }


    public static function getInstance() 
    {
        if (empty(self::$instance)) {
            self::$instance = new Db();
        }
        return self::$instance;
    }
    

    /**
     * Отключение от БД
     *
     */
    function close()
    {
        mysql_close();
    }


    /**
     * Выполнение sql-запроса написанного явным образом
     *
     * @param string $_sql Строка с SQL-запросом
     *
     * @throws \Exception
     * @return resource Возвращает указатель на результат запроса к БД
     */
    function query($_sql)
    {
        $config = Config::getInstance();
        if (isset($config->debug)) {
            \FB::log($_sql);
        }

        $errorText = '';
        $result = mysql_query(Util::convertToDb($_sql))
            or $errorText = "Не могу выполнить запрос: {$_sql}. Подробнее: " . mysql_error();

        if ($errorText != '') {
            Util::addError($errorText);
            $this->lastError = $errorText;
            throw new \Exception($errorText);
        }

        return $result;
    }


    public function getLastError()
    {
        return $this->lastError;
    }


    /**
     * Запрос в БД с выводом результатов в массив
     *
     * @param string $_sql Строка с SQL-запросом
     *
     * @return array Массив записей
     */
    function queryArray($_sql)
    {
        $result = $this->query($_sql);
        if ($result) {
            $Num  = mysql_numrows($result);
            $hash = $this->return_hash($result, $Num);
            return $hash;
        }
        return array();
    }


    /**
     * Создание таблицы данных под раздел
     *
     * @param string $table Название таблицы
     * @param $fields
     * @internal param array $arr Массив полей из настрое разделв для создания
     *
     * @return boolean Флаг успешности создания таблицы
     */
    function create($table, $fields)
    {
        $_sql = 'CREATE TABLE ' . $table . " \n(";
        foreach ($fields as $key => $value) {
            if ($value['sql'] == '') {
                // Пропускаем записи не предназначенные для БД
                continue;
            }
            $_sql .= $key . ' ' . $value['sql'] . ", \n";
        }
        $_sql = substr($_sql, 0, -3); // обрезание лишних знаков
        $_sql .= ');';

        $res = $this->query($_sql);

        return $res;
    }


    /**
     * Возвращение массива после запроса в БД
     *
     * @param resource $result Результат запроса к БД
     * @param int      $Num    Количество строк для извлечения
     *
     * @return array
     */
    function return_hash($result, $Num)
    {
        $i = 0;
        $hash = array();

        // построение массива вывода
        while ($i < $Num) {
            $row = mysql_fetch_array($result);
            foreach ($row as $key => $value) {
                if (is_string($key)) {
                    // удаление числовых индексов подмассива (только ассоциативные ключи)
                    $hash[$i][$key] = Util::convertFromDb($value);
                }
            }
            $i++;
        }
        return $hash;
    }


    /**
     * Осуществление выборки данных из таблицы $table
     *
     * @param string $table    Название таблицы
     * @param array  $id       Значение поля, по которому будет проводится отбор (WHERE),
     *                         можно использовать хэш с несколькими элементами
     * @param string $orderBy  Имя поля, по которому будут отсортирован список (можно использовать DESC)
     * @param string $idName   Имя поля, по которому будет проводится отбор; по умолчанию - "ID"
     * @param string $limit    Максимальное количество возвращаемых элементов
     *
     * @return hash Массив хэшей с содержимым таблицы $table
     */
    function select($table, $id, $orderBy = '', $idName = 'ID', $limit = NULL)
    {
        if (!$id) {
            // Если не указано значение => выйти
            return FALSE;
        }
        
        if ($orderBy) {
            $orderBy = ' ORDER BY ' . $orderBy;
        }

        $where = '';

        if (is_array($id)) { // если передан массив уточняющих параметров
            $separator = '';
            foreach ($id as $key => $value) {
                $where .= $separator . " ({$key} = '" . mysql_real_escape_string($value) . "')";
                $separator = ' AND ';
            }
        } else { // иначе только одно уточняющее значение
            if ($id == 'null') {
                $where .= "( ${idName} = null )";
            } else {
                $where .= "( {$idName} = '" . mysql_real_escape_string($id) ."')";
            }
        }

        $_sql = "SELECT * FROM {$table} WHERE {$where} {$orderBy}";

        // Если задано ограничение по выбираемому кол-ву, то устанавливаем его
        if ($limit != '') {
            $_sql .= ' LIMIT ' . $limit;
        }

        return $this->queryArray($_sql);
    }


    /**
     * Добавление записи в таблицу
     *
     * @param string $table  Название таблицы
     * @param array  $params Хэш с полями и новыми значениями строки
     *
     * @return int Идентификатор вставленной записи
     */
    function insert($table, $params)
    {
        $par_str = '';
        $sep = '';

        $key_ = key($params); // получение ключа

        if (is_string($key_)) { // для ассоциативного массива
            $query = 'SET';
            // формирование строки присваивающей значения полям таблицы
            foreach ($params as $key => $value) {
                if ($value) { // зачистка от лишних кавычек и апострофов
                    $value = mysql_real_escape_string($value);
                    $params[$key] = $value;
                }
                $par_str .= $sep . "{$key} = '{$value}'";
                $sep = ', ';
            }
        } else { // для стандартного массива
            $query = 'VALUES';
            $count_ = sizeof($params);
            $i = 0;
            while ($i < $count_) {
                $par_str .= $params[$i] . ', ';
                $i++;
            }
            $par_str = substr($par_str, 0, -2);
        }

        $sql = "INSERT INTO {$table} {$query} {$par_str}";
        
        if ($this->query($sql) === false) return false;

        return mysql_insert_id();
    }


    /**
     * Изменение содержимого таблицы
     *
     * @param string $table  Название таблицы
     * @param int    $id     Идентификатор изменяемой строки
     * @param array  $params Хэш с полями и новыми значениями строки
     * @param string $keyId
     *
     * @throws \Exception
     */
    function update($table, $id, $params, $keyId = 'ID')
    {
        $id = intval($id);
        if ($id < 1) {
            throw new \Exception('Указан неправильный ID для обновления: ' . $id);
        }
        if (!is_array($params)) {
            throw new \Exception('Переданы неправильные параметры для обновления: ' . print_r($params, true));
        }

        $paramStr = $separator = '';

        // Формирование строки присваивающей значения полям таблицы
        foreach ($params as $key => $value) {
            if ($value === 'NULL') {
                $paramStr .= "{$separator} {$key}=NULL";
            } else {
                // Зачистка от лишних кавычек и апострофов
                $value = mysql_real_escape_string($value);
                $paramStr .= "{$separator} {$key}='{$value}'";
            }
            $separator = ',';
        }
        // Формирование набора условий для обновления
        if (is_array($id)) {
            $where = '';
            $separator = '';
            foreach ($id as $key => $value) {
                $where .= $separator . "{$key}='{$value}'";
                $separator = ' AND ';
            }
        } else {
            $where = "{$keyId}='{$id}'";
        }

        $_sql = "UPDATE {$table} SET {$paramStr} WHERE {$where}";

        $this->query($_sql);
    }


    /**
     * Удаление строки из таблицы
     *
     * @param string           $table  Название таблицы
     * @param int|string|array $where  Идентификатор удаляемой строки
     * @param array    $relatedTables  Массив, в котором содержатся названия таблиц в которых
     *                                 могут быть установлены связи с удаляемой строкой
     *                                 + Поле, через которое связаны таблицы (например: action_types_id)
     *
     * @return boolean Истина, если удаление прошло успешно
     */
    public function delete($table, $where, $relatedTables = array())
    {
        if (intval($where) == $where) {
            $where = 'ID = ' . $where;
        } elseif (is_array($where)) {
            $whereStr = $separator = '';
            foreach ($where as $key => $value) {
                $whereStr .= $separator . "{$key} = '{$value}'";
                $separator = ' AND ';
            }
            $where = $whereStr;
        }

        $_sql = "DELETE FROM {$table} WHERE {$where}";
        $this->query($_sql);
        return true;
    }


    /**
     * Определение максимального значение колонки в БД
     *
     * @param string $table Название таблицы
     *
     * @return int
     */
    function db_max_value($table, $col, $where)
    {
        $_sql = 'select max(' . $col . ') from ' . $table . $where;
        $result = $this->query($_sql);
        if ($result) {
            $hash = array();
            $row = mysql_fetch_array($result);
            foreach ($row as $key => $value) { 
                // в таблице с отрицательными значениями 0 вызывает сбой
                // TODO что означает то, что написано выше? Надо разобраться...
                if ($value < 0) {
                    $value = 0;
                }
            }
            return $value;
        }
    }


    /**
     * Задание будущей позиции в БД для отображения на странице
     * TODO похоже этот метод вообще не нужен, надо проверить, как он срабатывает
     * при создании новых элементов и м.б. его вообще удалить
     *
     * @param string $table Название таблицы
     *
     * @return unknown
     */
    function db_set_pos ( $table, $pos, $max_pos )
    {
        if (!$table) {
            return 0;
        } // если не указана таблица

        if (!$max_pos) {
            return $pos;
        } // если не указана максимальная позиция

        if (!$pos) {
            $pos = $max_pos;
            return $pos;
        } // если не указана позиция

        if ($pos < $max_pos) { // есди задаваемая позиция < максимальной
            $_sql = 'update ' . $table . " set pos=pos+1 where pos between '" . $pos . "' and '" . $max_pos . "';";
            $result = $this->query($_sql);
            if (!$result) {
                return 0;
            } else {
                return $pos;
            }
        } else {
            return ($max_pos+1);
        }
    }


    /**
     * Функция проверки наличия таблицы в БД + ее заполненности
     *
     * @param string $table Имя таблицы
     *
     * @return boolean Флаг наличия такой таблицы
     */
    function db_ctrl($table)
    {
        $_sql = 'show tables';
        $result = $this->query($_sql);
        $Nums = mysql_num_rows($result);
        $hash = $this->return_hash($result, $Nums);

        // преобразование в одномерный массив 
        // (при данном запросе возвращается двумерный массив)
        
        $i = 0;
        while ($i < $Nums) {
            foreach ($hash[$i] as $key => $value) {
                $hash[$i] = $value;
            }
        $i++;
        }

        return in_array($table, $hash);
    }


    /**
     * Формирование запроса на обновление cid адресов из массива ID + cid
     *
     * @param string $table Название обновляемой таблицы
     * @param array  $arr   Массив, содержащий ID и cid
     *
     * @return boolean Флаг успешности обновления
     */
    function update_cid($table, $arr)
    {
        $res = 1;
        foreach($arr as $v) {
            $id = $v['ID'];
            $cid = $v['cid'];
            $_sql = "UPDATE {$table} SET cid='{$cid}' WHERE ID={$id}";
            $result = $this->query($_sql);
            if ($result) {
                $res = $res * 1;
            } else {
                $res = $res * 0;
            }
        }
        return $res;
    }


    /**
     * Пересортировка элементов в случае удаления...
     *
     * @param string $table Таблица в которой проводят изменения
     * @param string $ccid  Удаляемый CID
     * @param int    $lvl   На каком уровне проходит удаление
     * @param string $cid   Шаблон для изменения CID
     */
    function resort_cid($table, $ccid, $lvl, $cid)
    {
        $int = $cid->cid_interval($ccid, $lvl-1); //узнаем интервал адресов
        
        $query = "SELECT ID, cid FROM {$table} WHERE cid like '{$int['undo']}%' ORDER BY cid;";
        $lst = $this->queryArray($query);
        
        foreach ($lst as $k => $v) {
            if ($v['cid'] > $ccid) {
                $ncid = $cid->build_cid($v['cid'], $lvl, -1);
                $arr[] = array('ID'  => $v['ID'],
                               'cid' => $ncid);
            }
        }
        $this->update_cid($table, $arr);
    }


    /**
     * Пересортировка элементов в таблице с полем pos в случае удаления...
     *
     * @param string $table Таблица в которой произошло изменение
     * @param string $where
     */
    function resort_pos($table, $where = '')
    {
        $lst = $this->queryArray("SELECT ID, pos FROM {$table} {$where} ORDER BY pos");
        $i = 1;
        foreach ($lst as $k => $v) {
            $v['pos'] = $i;
            $this->update_row($table, $v, $v['ID']);
            $i++;
        }
    }


    /**
     * Определение максимального номера для таблицы
     *
     * @param string $tb_prefix - текстовая часть названия таблицы
     * @return int
     */
    function next_num($tb_prefix)
    {
        // получаем список таблиц
        $list = mysql_list_tables($this->dbName);

        // просканировать таблицы на совпадение с tb_prefix
        $num = 0;
        for ($i = 0; $i < mysql_num_rows($list); $i++) {
            $value = mysql_tablename($list, $i);
            if (strpos($value, $tb_prefix . '_') === 0) {
                $num_cur = substr($value, strpos( $value, '_' ) + 1);
                // выбор максимального номера
                if ($num_cur > $num) {
                    $num = $num_cur;
                }
            }
        }
        $num++;
        return $num;
    }

}