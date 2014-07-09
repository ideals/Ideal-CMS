<?php
namespace Ideal\Structure\Home\Site;

use Ideal\Structure\Part;
use Ideal\Core\Config;
use Ideal\Core\Db;

class ModelAbstract extends Part\Site\Model
{

    public function construct($prevStructure)
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


    public function detectPageByUrl($path, $url)
    {
        $db = Db::getInstance();

        $_sql = "SELECT * FROM {$this->_table} WHERE url=:url LIMIT 1";

        $list = $db->select($_sql, array('url' => $url)); // получение всех страниц, соответствующих частям url

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