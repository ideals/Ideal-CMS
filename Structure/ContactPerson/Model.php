<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\ContactPerson;

use Ideal\Core\Db;
use Ideal\Core\Config;

/**
 * Класс для работы с контактными лицами
 */
class Model
{
    /** @var string E-mail контактного лица */
    protected $email;

    /** @var string Телефон контактного лица */
    protected $phone;

    /** @var string Client ID контактного лица */
    protected $clientId;

    /** @var string Имя контактного лица */
    protected $name;

    /**
     * @param string $email E-mail контактного лица
     * @return Model
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $phone Телефон контактного лица
     * @return Model
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @param string $name Имя контактного лица
     * @return Model
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $clientId Client ID контактного лица
     * @return Model
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
        return $this;
    }

    /**
     * Получает идентификатор контактного лица
     * @throws \Exception
     * @return mixed Идентификатор контактного лица или false в случае невозможности определения по данным
     * или множественного определения
     */
    public function getContactPersonId()
    {
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

        // Если нет условий для поиска, то и искать нечего
        if ($where) {
            $fields = array('table' => $config->getTableByName('Ideal_ContactPerson'));
            $contactPerson = $db->select("SELECT * FROM &table WHERE {$where}", $par, $fields);
            if ($contactPerson && count($contactPerson) == 1) {
                return $contactPerson[0]['ID'];
            }
        }
        return false;
    }
}
