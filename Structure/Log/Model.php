<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Log;

use Ideal\Core\Config;
use Ideal\Core\Db;
use Ideal\Structure\User\Model as UserModel;

/**
 * Класс для работы с заказчиками
 */
class Model
{
    /**
     * @var string Название таблицы в базе
     */
    protected $table;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->table = $config->db['prefix'] . 'ideal_structure_log';
    }

    /**
     * Записывает информацию в лог
     *
     * @param string $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, $context)
    {
        $config = Config::getInstance();
        $db = Db::getInstance();
        $user = UserModel::getInstance();
        $json = array();

        if (isset($context['model'])) {
            $model = $context['model'];
            $structure = $config->getStructureByClass(get_class($model));
            $pageData = $model->getPageData();
            $json['structure_id'] = $structure['ID'];
            $json['element_id'] = $pageData['ID'];
        }

        $par = array(
            'date_create' => time(),
            'level' => $level,
            'user_id' => $user->data['ID'],
            'type' => $context['type'],
            'message' => $message,
            'json' => json_encode($json, JSON_UNESCAPED_UNICODE),
        );

        $db->insert($this->table, $par);
    }
}
