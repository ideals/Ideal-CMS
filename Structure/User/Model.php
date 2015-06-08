<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\User;

use Ideal\Core\Config;
use Ideal\Core\Db;

/**
 * Класс для работы с пользователем
 */
class Model
{

    /** @var  mixed Хранит в себе копию соответствующего объекта поля (паттерн singleton) */
    protected static $instance;

    /** @var array Массив с данными пользователя */
    public $data = array();

    /** @var string Последнее сообщение об ошибке */
    public $errorMessage = '';

    /** @var string Наименование сессии и cookies */
    protected $seance = '';

    /** @var array Считанная сессия этого сеанса */
    protected $session = array();

    /** @var string Название таблицы, в которой хранятся данные пользователей */
    protected $table = 'ideal_structure_user';

    /** @var string Поле, используемое в качестве логина */
    protected $loginRow = 'email';

    /** @var string Название поля логина (используется для выдачи уведомлений) */
    protected $loginRowName = 'e-mail';

    /**
     * Считывает данные о пользователе из сессии
     */
    public function __construct()
    {
        // Запуск сессий только в случае, если они не запущены
        if (session_id() == '') {
            // Для корректной работы этого класса нужны сессии
            session_start();
        }

        $config = Config::getInstance();

        // Устанавливаем имя связанной таблицы
        $this->table = $config->db['prefix'] . $this->table;

        // TODO сделать считывание переменной сеанса из конфига

        // Инициализируем переменную сеанса
        if ($this->seance == '') {
            $this->seance = $config->domain;
        }

        // Загружаем данные о пользователе, если запущена сессия
        if (isset($_SESSION[$this->seance])) {
            $this->session = unserialize($_SESSION[$this->seance]);
            $this->data = $this->session['user_data'];
        } else {
            $this->data = array();
        }
    }

    /**
     * Обеспечение паттерна singleton
     *
     * Особенность — во всех потомках нужно обязательно определять свойство
     * protected static $instance
     *
     * @return mixed
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new Model();
        }
        return self::$instance;
    }

    /**
     * При уничтожении объекта данные пользователя записываются в сессию
     */
    public function __destruct()
    {
        if (isset($this->session['user_data'])) {
            $_SESSION[$this->seance] = serialize($this->session);
        }
    }

    /**
     * Проверка залогинен ли пользователь
     *
     * @return bool Если залогинен — true, иначе — false
     */
    public function checkLogin()
    {
        // Если пользователь не залогинен - возвращаем false
        return isset($this->data['ID']);
    }

    /**
     * Проверка введённого пароля
     *
     * В случае удачной авторизации заполняется поле $this->data
     *
     * @param string $login Имя пользователя
     * @param string $pass  Пароль в md5()
     *
     * @return bool true — если удалось авторизоваться, false — если не удалось
     */
    public function login($login, $pass)
    {
        $login = trim($login);
        $pass = trim($pass);

        // Если не указан логин или пароль - выходим с false
        if (!$login || !$pass) {
            $this->errorMessage = "Необходимо указать и {$this->loginRowName}, и пароль.";
            return false;
        }

        // Получаем пользователя с указанным логином
        $db = Db::getInstance();
        $_sql = "SELECT * FROM {$this->table} WHERE is_active = 1 AND {$this->loginRow} = :login";
        $user = $db->select($_sql, array('login' => $login));
        if (count($user) == 0) {
            $this->errorMessage = "Неверно указаны {$this->loginRowName} или пароль.";
            return false;
        }
        $user = $user[0];

        // Если пользователь с таким логином не нашлось, или пароль не совпал - выходим с false
        if (($user[$this->loginRow] == '')
            || (crypt($pass, $user['password']) != $user['password'])
        ) {
            // Увеличиваем значение счётчика неудачных попыток авторизации если он меньше 12
            if ($user['counter_failures'] < 12) {
                $db->update($this->table)->set(array('counter_failures' => $user['counter_failures'] + 1));
                $db->where($this->loginRow . ' = :login', array('login' => $login))->exec();
            }

            $this->logout();
            $this->errorMessage = "Неверно указаны {$this->loginRowName} или пароль.";

            // Придерживаем ответ на значение равное умножению счётчика неудачных попыток авторизации на 5
            sleep($user['counter_failures'] * 5);
            return false;
        }

        // Обнуляем счётчик неудачных попыток авторизации
        $user['counter_failures'] = 0;

        // Если пользователь находится в процессе активации аккаунта
        if ($user['act_key'] != '') {
            $this->errorMessage = 'Этот аккаунт не активирован.';
            return false;
        }

        $user['last_visit'] = time();
        $this->data = $user;

        // Обновляем запись о последнем визите пользователя
        $db->update($this->table)->set($user);
        $db->where('ID=:id', array('id' => $user['ID']))->exec();

        // Записываем данные о пользователе в сессию
        $this->session['user_data'] = $this->data;
        return true;
    }

    /**
     * Выход пользователя с удалением данных из сессии
     */
    public function logout()
    {
        $this->data = $this->session = array();
        unset($_SESSION[$this->seance]);
    }

    /**
     * Установка произвольного поля для логина пользователя
     *
     * @param string $loginRow Название поля (например, email)
     * @param string $loginRowName Название поля для отображения уведомлений (например, e-mail)
     */
    public function setLoginField($loginRow, $loginRowName)
    {
        $this->loginRow = $loginRow;
        $this->loginRowName = $loginRowName;
    }
}
