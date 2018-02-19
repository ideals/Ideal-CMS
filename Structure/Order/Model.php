<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Order;

use Ideal\Core\Config;
use Ideal\Core\Db;

class Model
{
    protected $table = 'ideal_structure_order';

    public function __construct()
    {
        $config = Config::getInstance();
        $this->table = $config->db['prefix'] . $this->table;
    }

    public function getCrmOrders()
    {
        $db = Db::getInstance();
        $sql = "SELECT * FROM {$this->table} ORDER BY date_create DESC";
        $items = $db->select($sql);
        foreach ($items as &$item) {
            $item['date_create_str'] = date('d.m.Y H:i', $item['date_create']);
        }
        return $items;
    }
}
