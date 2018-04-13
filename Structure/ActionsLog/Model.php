<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\ActionsLog;

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

    /**
     * @var string Действие совершаемое над элементом
     */
    protected $action;

    /**
     * @var string Модель структуры с которой совершается действие
     */
    protected $model;

    public function __construct($model, $action)
    {
        $config = Config::getInstance();
        $this->table = $config->db['prefix'] . 'ideal_structure_actionslog';
        $this->model = $model;
        $this->action = $action;
    }

    /**
     * Записывает информацию в лог
     */
    public function addToLog()
    {
        $config = Config::getInstance();
        $db = Db::getInstance();

        $user = UserModel::getInstance();
        $structure = $config->getStructureByClass(get_class($this->model));

        // Формируем строку для обозначения элемента
        $pageData = $this->model->getPageData();
        $element = '(' . $pageData['ID'] . ')';
        if (isset($pageData['name'])) {
            $element .= ' ' . $pageData['name'];
        }
        if (isset($pageData['url'])) {
            $element .= ' - ' . $pageData['url'];
        }


        $par = array(
            'date_create' => time(),
            'user' => $user->data['ID'],
            'structure' => $structure['ID'],
            'element' => $element,
            'action' => $this->action,
        );
        $db->insert($this->table, $par);
    }
}
