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
        $config = Config::getInstance();
        $modelStructures = array_unique($this->obj->params['structures']);

        // Получаем список структур, которые можно создавать в этой структуре
        foreach ($config->structures as $structure) {
            if (in_array($structure['structure'], $modelStructures)) {
                $structures[] = $structure['structure'];
            }
        }

        foreach ($structures as $value) {
            $folderName = str_replace('\\', '/', Util::getClassName($value, 'Structure'));
            $parts = explode('/', $folderName);
            $moduleName = $parts[1];
            if ($moduleName == 'Ideal') {
                $folderPartNames = array('Ideal', 'Ideal.c');
                $moduleName = '';
            } else {
                $folderPartNames = array('Mods', 'Mods.c');
                $moduleName = $moduleName . '/';
            }
            $structureName = $parts[3];
            foreach ($folderPartNames as $folderPartName) {
                $twigTplRootScanFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . $folderPartName . '/' . $moduleName . 'Structure/' . $structureName . '/Site/';

                $nameTpl = '/.*\.twig$/';
                $templates = scandir($twigTplRootScanFolder);

                // Получаем список доступных для выбора шаблонов
                foreach ($templates as $node) {
                    if (preg_match($nameTpl, $node)) {
                        $list[$value][$node] = $node;
                    }
                }
            }
        }
        return $list;
    }
}
