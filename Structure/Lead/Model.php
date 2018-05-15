<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Lead;

use Ideal\Core\Config;

class Model
{
    protected $table = 'ideal_structure_lead';

    public function __construct()
    {
        $config = Config::getInstance();
        $this->table = $config->db['prefix'] . $this->table;
    }
}
