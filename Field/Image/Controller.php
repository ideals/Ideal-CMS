<?php
namespace Ideal\Field\Image;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected static $instance;


    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        // TODO сделать возможность посмотреть картинку по щелчку на ссылке (не закрывая окна)
        return '<div class="input-group">'
            . '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value .'"><span class="input-group-btn">'
            . '<button class="btn" onclick="showFinder(\'' . $this->htmlName . '\'); return false;" >Выбрать</button>'
            . '</span></div>';
    }

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
            if ($pos == 0) continue;
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
            if (!file_exists($fileName)) continue;
            if (!is_writable($fileName)) {
                return 'Не могу удалить файл старой resized-картинки ' . $fileName;
            }
            unlink($fileName);
        }
        return '';
    }
}
