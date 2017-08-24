<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\YandexWebmaster;

use Ideal\Addon\AbstractAdminModel;

class AdminModel extends AbstractAdminModel
{
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
        // Если страница только создаётся, данные извлекать неоткуда
        if (empty($pageData['template'])) {
            return '';
        }
        // Получаем данные из полей
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
