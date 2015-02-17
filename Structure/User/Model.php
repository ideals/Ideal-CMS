<?php
namespace Ideal\Structure\User;

use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Класс для работы с пользователем
 *
 */

session_start();

class Model
{

        static $instance; // массив с данными пользователя

    public $data = array(); // последнее сообщение об ошибке

    public $errorMessage = ''; // наименование сессии и cookies

    protected $_seance = ''; // считанная сессия этого сеанса

protected $_session = array();

    protected $_table = 'ideal_structure_user';

    protected $loginRow = 'email';

    protected $loginRowName = 'e-mail';

    /**
     * Считывает данные о пользователе из сессии
     *
     */
    public function __construct()
    {
        $config = Config::getInstance();

        // Устанавливаем имя связанной таблицы
        $this->_table = $config->db['prefix'] . $this->_table;

        // TODO сделать считывание переменной сеанса из конфига

        // Инициализируем переменную сеанса
        if ($this->_seance == '') {
            $this->_seance = $config->domain;
        }

        // Загружаем данные о пользователе, если запущена сессия
        if (isset($_SESSION[$this->_seance])) {
            $this->_session = unserialize($_SESSION[$this->_seance]);
            $this->data = $this->_session['user_data'];
        } else {
            $this->data = array();
        }
    }

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Model();
        }
        return self::$instance;
    }

    public function __destruct()
    {
        if (isset($this->_session['user_data'])) {
            $_SESSION[$this->_seance] = serialize($this->_session);
        }
    }

    public function checkLogin()
    {
        // Если пользователь не залогинен - возвращаем FALSE
        return isset($this->data['ID']);
    }

    /**
     * Проверка введённого пароля
     *
     * В случае удачной авторизации заполняется поле $this->data
     *
     * @param $login Имя пользователя
     * @param $pass  Пароль в md5()
     *
     * @return bool true, если удалось залогиниться, false, если не удалось
     */
    public function login($login, $pass)
    {
        $login = trim($login);
        $pass = trim($pass);

        // Если не указан логин или пароль - выходим с false
        if (!$login OR !$pass) {
            $this->errorMessage = "Необходимо указать и {$this->loginRowName}, и пароль.";
            return false;
        }

        // Получаем пользователя с указанным логином
        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$this->_table} WHERE is_active = 1 AND {$this->loginRow} = :login";
        $delayTime = $user['count_login'] * 5;
        if ($delayTime>60) {$delayTime = 60;}
        $user = $db->select($_sql, array('login' => $login));
        
        if (count($user) == 0) {
            $this->errorMessage = "Неверно указаны {$this->loginRowName} или пароль.";
            $delayTime = $user['count_login'] * 5;
            if ($delayTime>60) {$delayTime = 60;}
            sleep($delayTime);
            $user['count_login'] += 1;
            $db->update($this->_table)->set($user);
            $db->where('ID=:id', array('id' => $user['ID']))->exec();
            return false;
        }
        $user = $user[0];

        // Если юзера с таким логином не нашлось, или пароль не совпал - выходим с false
        if (($user[$this->loginRow] == '')
            OR (crypt($pass, $user['password']) != $user['password'])
        ) {
            $this->logout();
            $this->errorMessage = "Неверно указаны {$this->loginRowName} или пароль.";
            $delayTime = $user['count_login'] * 5;
            if ($delayTime>60) {$delayTime = 60;}
            sleep($delayTime);
            $user['count_login'] += 1;
            $db->update($this->_table)->set($user);
            $db->where('ID=:id', array('id' => $user['ID']))->exec();
            return false;
        }

        // Если пользователь находится в процессе активации аккаунта
        if ($user['act_key'] != '') {
            $this->errorMessage = 'Этот аккаунт не активирован.';
            return false;
        }

        $user['last_visit'] = time();
        $this->data = $user;
        $delayTime = ($user['count_login']-1) * 5;
        sleep($delayTime);
        $user['count_login'] = 0;
        // Обновляем запись о последнем визите пользователя
        $db->update($this->_table)->set($user);
        $db->where('ID=:id', array('id' => $user['ID']))->exec();

        // Записываем данные о пользователе в сессию
        $this->_session['user_data'] = $this->data;
        return true;
    }

    /**
     * Выход юзера
     */
    public function logout()
    {
        $this->data = $this->_session = array();
        unset($_SESSION[$this->_seance]);
    }

    public function setLoginField($loginRow, $loginRowName)
    {
        $this->loginRow = $loginRow;
        $this->loginRowName = $loginRowName;
    }
}