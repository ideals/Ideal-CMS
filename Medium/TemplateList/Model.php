<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\TemplateList;

use Ideal\Core\Util;
use Ideal\Core\Config;
use Ideal\Medium\AbstractModel;

/**
 * Медиум для получения списка шаблонов, которые можно выбрать для отображения структуры $obj
 */
class Model extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        // Определяем папку для сканирования доступных шаблонов
        $config = Config::getInstance();
        $scanFolderName = preg_split('/\\\(.*)/iU', get_class($this->obj), 4);
        array_pop($scanFolderName);
        $scanFolderName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . implode('/', $scanFolderName). '/Site';
        $nameTpl = '/.*\.twig$/';
        $templates = scandir($scanFolderName);

        // Получаем список доступных для выбора шаблонов
        foreach ($templates as $node) {
            if (preg_match($nameTpl, $node)) {
                $list[$node] = $node;
            }
        }
        return $list;
    }
}
