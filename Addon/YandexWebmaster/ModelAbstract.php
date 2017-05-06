<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\YandexWebmaster;

use Ideal\Addon;

class ModelAbstract extends Addon\AbstractModel
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);
        return $this->pageData;
    }

    /**
     * Получение контента из полей, указанных для отправки в Яндекс.Вебмастер
     *
     * @return string
     */
    public function getContentFromFields()
    {
        $content = '';
        $pageData = $this->parentModel->getPageData();
        $this->parentModel->initPageData($pageData);
        $pageData = $this->parentModel->getPageData();
        // Получаем данные из полей определённых в параметре "content_fields"
        foreach ($this->parentModel->fields['addon']['webmaster'] as $template => $value) {
            if ($template != $pageData['template']) {
                // Пропускаем неподходящие шаблоны
                continue;
            }
            foreach ($value as $field) {
                if (is_array($field)) {
                    // В массиве находятся сведения о полях в аддонах
                    foreach ($field as $k => $addonField) {
                        $content .= $pageData['addons'][$k][$addonField];
                    }
                } else {
                    // Если не массив, значит это просто поле объекта
                    $content .= "\n" . $pageData[$field];
                }
            }
        }
        return $content;
    }
}
