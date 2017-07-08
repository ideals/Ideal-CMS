<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Core\Util;

/**
 * Класс для работы с заказчиками
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
    public function getCustomerId()
    {
        $this->clientId = Util::getGACID();
        $this->clientId = $this->clientId ? $this->clientId : '';
        $config = Config::getInstance();
        $db = Db::getInstance();
        $par = array(
            'phones' => $db->escape_string($this->phone),
            'client_ids' => $db->escape_string($this->clientId),
            'emails' => $db->escape_string($this->email)
        );
        $where = '';
        foreach ($par as $key => $value) {
            if ($value) {
                $where .= " OR {$key} LIKE '%{$value}%'";
            }
        }
        $where = ltrim($where, ' OR');
        $fields = array('table' => $config->db['prefix'] . 'ideal_structure_crm');
        $customer = $db->select("SELECT * FROM &table WHERE {$where} LIMIT 1", null, $fields);

        // Если нашли заказчика, то возвращаем его идентификатор
        if ($customer) {
            $customerId = $customer[0]['ID'];
        } else { // Если заказчика не нашли, то создаём его с заданными данными
            foreach ($par as $key => $value) {
                if ($value) {
                    $par[$key] = json_encode(array($value));
                }
            }
            $par['name'] = $this->name;
            $par['date_create'] = time();

            // Получаем идентификатор справочника "Заказчики" для правильного построения поля "prev_structure"
            $prevStructureId = $db->select(
                "SELECT ID FROM {$config->db['prefix']}ideal_structure_datalist WHERE structure = 'Ideal_Crm'"
            );
            $par['prev_structure'] = '3-' . $prevStructureId['0']['ID'];
            $customerId = $db->insert($config->db['prefix'] . 'ideal_structure_crm', $par);
        }
        return $customerId;
    }
}
