<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Image;

use Ideal\Core\Config;
use Ideal\Field\AbstractController;

/**
 * Поле редактирования картинки
 *
 * В поле редактирования присутствует картинка и кнопка выбора/загрузки картинки с сервера
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'img' => array(
 *         'label' => 'Картинка',
 *         'sql'   => 'varchar(255)',
 *         'type'  => 'Ideal_Image'
 *     ),
 */
class Controller extends AbstractController
{
    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        return '<div class="input-group">'
        . '<span class="input-group-addon" style="padding: 0px 5px">'
        // миниатюра картинки
        . '<img id="' . $this->htmlName . 'Img" src="' . $value . '" style="max-height:32px"></span>'
        . '<input type="text" class="form-control" name="' . $this->htmlName
        . '" id="' . $this->htmlName
        . '" value="' . $value
        . '" onchange="$(\'#' . $this->htmlName . 'Img\').attr(\'src\', $(this).val());">' // замена миниатюры картинки
        . '<span class="input-group-btn">'
        . '<button class="btn" onclick="showFinder(\'' . $this->htmlName . '\'); return false;" >Выбрать</button>'
        . '</span></div>';
    }

    /**
     * {@inheritdoc}
     */
    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // Удаляем resized-варианты старой картинки
        $value = $this->getValue();
        $item['message'] .= $this->imageRegenerator($value);

        // Удаляем resized-варианты новой картинки
        $value = $this->pickupNewValue();
        $item['message'] .= $this->imageRegenerator($value);

        return $item;
    }

    /**
     * Удаление resized-вариантов картинки
     *
     * @param string $value
     * @return string
     */
    protected function imageRegenerator($value)
    {
        $config = Config::getInstance();
        if ($value == '' || $config->allowResize == '') {
            return '';
        }

        // Из .htaccess определяем папку с resized-изображениями
        $folder = '';
        $htaccess = file(DOCUMENT_ROOT . '/.htaccess');
        foreach ($htaccess as $v) {
            $pos = strpos($v, 'Ideal/Library/Resize/image.php');
            if ($pos == 0) {
                continue;
            }
            preg_match('/\^(.*)\/\(/', $v, $matches);
            if (is_null($matches)) {
                return 'Не могу определить по файлу .htaccess папку для resized-картинок. '
                . 'Правило должно быть вида ^images/resized/(.*)';
            }
            $folder = DOCUMENT_ROOT . '/' . $matches[1] . '/';
            break;
        }

        if ($folder == '') {
            return 'В корневом .htaccess не задано правило для resized-картинок';
        }

        // Удаляем старое изображение из resized-папок
        $allowResize = explode('\n', $config->allowResize);
        foreach ($allowResize as $v) {
            $fileName = $folder . $v . $value;
            if (!file_exists($fileName)) {
                continue;
            }
            if (!is_writable($fileName)) {
                return 'Не могу удалить файл старой resized-картинки ' . $fileName;
            }
            unlink($fileName);
        }
        return '';
    }
}
