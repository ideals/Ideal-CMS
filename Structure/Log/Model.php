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
 * Класс обеспечивающий логирование
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
     * Авария, система неработоспособна.
     *
     * @param string $message
     * @param array $context
     */
    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Тревога, меры должны быть предприняты незамедлительно.
     *
     * @param string $message
     * @param array $context
     */
    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Критическая ошибка, критическая ситуация.
     *
     * @param string $message
     * @param array $context
     */
    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Ошибка на стадии выполнения, не требующая неотложного вмешательства,
     * но требующая протоколирования и дальнейшего изучения.
     *
     * @param string $message
     * @param array $context
     */
    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    /**
     * Предупреждение, нештатная ситуация, не являющаяся ошибкой.
     *
     * @param string $message
     * @param array $context
     */
    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Замечание, важное событие.
     *
     * @param string $message
     * @param array $context
     */
    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Информация, полезные для понимания происходящего события.
     *
     * @param string $message
     * @param array $context
     */
    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    /**
     * Информация, полезные для понимания происходящего события.
     *
     * @param string $message
     * @param array $context
     */
    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Протоколирование с произвольным уровнем.
     *
     * @param string $level Константа одного из уровней протоколирования
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = array())
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
