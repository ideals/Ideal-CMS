<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Home\Site;

use Ideal\Structure\Part\Site\Model;
use Ideal\Core\Config;
use Ideal\Core\Db;

class ModelAbstract extends Model
{
    public function construct($prevStructure): void
    {
        $this->prevStructure = $prevStructure;

        $config = Config::getInstance();

        // Находим начальную структуру
        $structures = $config->structures;
        $structure = reset($structures);

        $this->params = $structure['params'];
        $this->fields = $structure['fields'];

        $this->_table = strtolower($config->db['prefix'] . 'Structure_' . $structure['structure']);
    }

    public function detectPageByUrl($path, $url): self
    {
        $db = Db::getInstance();

        $_sql = sprintf('SELECT * FROM %s WHERE BINARY url=:url LIMIT 1', $this->_table);

        $list = $db->select($_sql, ['url' => $url]); // получение всех страниц, соответствующих частям url

        // Страницу не нашли, возвращаем 404
        if (!isset($list[0]['cid'])) {
            $this->path = $path;
            $this->is404 = true;
            return $this;
        }

        $this->path = array_merge($path, $list);

        return $this;
    }
}
