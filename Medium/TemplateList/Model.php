<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
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

        $objClassName = get_class($this->obj); // определяем название класса модели редактируемого элемента
        $objClassNameSlice = explode('\\', $objClassName);

        // Получаем название текущего типа структуры
        $modelStructures = array($objClassNameSlice[0] . '_' . $objClassNameSlice[2]);

        // Заносим уже введённое значение в список доступных шаблонов, так как оно может быть кастомным
        $pageData = $this->obj->getPageData();
        if (!empty($pageData['template'])) {
            $list[$modelStructures[0]][$pageData['template']] = $pageData['template'];
        }

        // Проверяем какие типы можно создавать в этом разделе
        if (isset($this->obj->params['structures']) && !empty($this->obj->params['structures'])) {
            // Учитываем все возможные типы из этого раздела при построении списка шаблонов для отображения
            $modelStructures = array_unique($this->obj->params['structures']);
        }

        // Получаем список структур, которые можно создавать в этой структуре
        foreach ($config->structures as $structure) {
            if (in_array($structure['structure'], $modelStructures)) {
                $structures[] = $structure['structure'];
            }
        }

        // Проходим по списку всех возможных типов из этого раздела и ищем в них шаблоны для отображения
        // TODO учесть вевроятность снижения производительности при наличии достстоно большого количества типов структур
        foreach ($structures as $value) {
            $folderName = str_replace('\\', '/', Util::getClassName($value, 'Structure'));
            $parts = explode('/', $folderName);
            $moduleName = $parts[1];
            if ($moduleName == 'Ideal') {
                $folderPartNames = array('Ideal', 'Ideal.c');
                $moduleName = '';
                $folderName = str_replace('/Ideal', '', $folderName);
            } else {
                $folderPartNames = array('Mods', 'Mods.c');
                $moduleName = $moduleName . '/';
            }
            $structureName = $parts[3];
            foreach ($folderPartNames as $folderPartName) {
                $twigTplRootScanFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder
                    . '/' . $folderPartName . '/' . $moduleName . 'Structure/' . $structureName . '/Site/';

                // Проверяем на существование директорию перед сканированием.
                if (is_dir($twigTplRootScanFolder)) {
                    $nameTpl = '/.*\.twig$/';
                    $templates = scandir($twigTplRootScanFolder);

                    // Получаем список доступных для выбора шаблонов
                    foreach ($templates as $node) {
                        if (preg_match($nameTpl, $node)) {
                            $list[$value][$node] = $folderPartName . $folderName . '/' . $node;
                        }
                    }
                }
            }
        }
        return $list;
    }
}
