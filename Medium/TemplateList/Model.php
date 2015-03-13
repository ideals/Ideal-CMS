<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Medium\TemplateList;

use Ideal\Core\Config;
use Ideal\Core\Util;
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
        $modelStructures = array_unique($this->obj->params['structures']);

        // Получаем список структур, которые можно создавать в этой структуре
        foreach ($config->structures as $structure) {
            if (in_array($structure['structure'], $modelStructures)) {
                $structures[] = $structure['structure'];
            }
        }

        foreach ($structures as $value) {
            $scanFolderName = str_replace('\\', '/', Util::getClassName($value, 'Structure'));
            $scanFolderName = DOCUMENT_ROOT . '/' . $config->cmsFolder . $scanFolderName . '/Site';
            $nameTpl = '/.*\.twig$/';
            $templates = scandir($scanFolderName);

            // Получаем список доступных для выбора шаблонов
            foreach ($templates as $node) {
                if (preg_match($nameTpl, $node)) {
                    $list[$value][$node] = $node;
                }
            }
        }
        return $list;
    }
}
