<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\SiteData;

/**
 * Содержит методы, которые могут быть исполнены когда изменяется значение в настройках сайта
 *
 * Используется в ConfigPhp.php
 */
class ApplyChange
{
    /** @var mixed Данные полей для использования в методах-реакциях на изменения */
    protected $value;

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     *  Добаляет/убирает строки в .htaccess отвечающие за браузерное кэширование
     */
    public function browserCacheChange()
    {
        $filePath = DOCUMENT_ROOT . '/.htaccess';
        if (file_exists($filePath)) {
            $fileContent = file_get_contents($filePath);
            if ($this->value) {
                $addString = <<<string
                
<IfModule mod_expires.c>
Header append Cache-Control "public"
FileETag MTime Size
ExpiresActive On
ExpiresDefault "access plus 0 minutes"
ExpiresByType image/ico "access plus 1 years"
ExpiresByType text/css "access plus 1 years"
ExpiresByType text/javascript "access plus 1 years"
ExpiresByType image/gif "access plus 1 years"
ExpiresByType image/jpg "access plus 1 years"
ExpiresByType image/jpeg "access plus 1 years"
ExpiresByType image/bmp "access plus 1 years"
ExpiresByType image/png "access plus 1 years"
</IfModule>

string;
            } else {
                $addString = "\n";
            }
            $pattern = '/(# browser cache)(.*)(# end browser cache)/isU';
            $fileContent = preg_replace($pattern, '$1' . $addString . '$3', $fileContent);
            file_put_contents($filePath, $fileContent);
        }
    }
}
