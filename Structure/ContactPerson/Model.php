<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\ContactPerson;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;

/**
 * Класс для работы с контактными лицами
 */
class Model
{
    /** @var string E-mail заказчика */
    protected $email;

    /** @var string Телефон заказчика */
    protected $phone;

    /** @var string Client ID заказчика из Google Analytics */
    protected $clientId;

    /** @var string Имя заказчика */
    protected $name;

    /**
     * @param string $email E-mail заказчика
     * @return Model
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $phone Телефон заказчика
     * @return Model
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @param string $name Имя заказчика
     * @return Model
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Получает идентификатор заказчика
     */
    public function getContactPerson()
    {
        $this->clientId = Util::getGACID();
        $this->clientId = $this->clientId ? $this->clientId : '';
        $config = Config::getInstance();
        $db = Db::getInstance();
        $par = array(
            'phone' => $db->escape_string($this->phone),
            'client_id' => $db->escape_string($this->clientId),
            'email' => $db->escape_string($this->email)
        );
        $where = '';
        foreach ($par as $key => $value) {
            if ($value) {
                $where .= " OR {$key} = :{$key}";
            }
        }
        $where = ltrim($where, ' OR');
        $fields = array('table' => $config->db['prefix'] . 'ideal_structure_contactperson');
        $contactPerson = $db->select("SELECT * FROM &table WHERE {$where} LIMIT 1", $par, $fields);
        return $contactPerson;
    }
}
