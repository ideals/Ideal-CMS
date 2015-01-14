<?php
/**
 * Изменение размера изображения
 */
namespace Resize;

class Resize
{
    /** @var int $width Ширина нового изображения */
    protected $width;

    /** @var int $height Высота нового изображения */
    protected $height;

    /** @var array $color Цвет фона изображения */
    protected $color = null;

    /** @var string $sizeDelimiter Разделитель значений размеров изображения */
    protected $sizeDelimiter = 'x';

    /** @var string $fullNameOriginal Полное имя изображения с исходным размером */
    protected $fullNameOriginal;

    /** @var string $fullNameResized Полное имя изображения с изменённым размером */
    protected $fullNameResized;

    /**
     * @param string $image Строка содержащая параметры требуемого изображения, а также путь к исходному изображению
     */
    public function run($image)
    {
        $this->setImage($image);
        $rImage = $this->resizeImage();
        $this->saveImage($rImage, $image);
        $this->echoImage($rImage);
    }

    /**
     * Разбор параметров требуемого изображения и их проверка
     *
     * @param string $image Строка содержащая размеры требуемого изображения, а также путь к исходному изображению
     */
    protected function setImage($image)
    {
        $imgInfo = explode('/', $image);

        // Получаем требуемые размеры нового изображения
        $imgSize = explode($this->sizeDelimiter, $imgInfo[0]);

        // Проверяем существование необходимых параметров ширины и высоты
        if (isset($imgSize[0]) && isset($imgSize[1])) {
            $this->width = intval($imgSize[0]);
            $this->height = intval($imgSize[1]);
        } else {
            $this->exit404();
        }

        // Проверяем существование параметра цвет
        if (isset($imgSize[2])) {
            // Преобразование цвета фона
            $this->color = sscanf('#' . $imgSize[2], '#%2x%2x%2x');
        }

        // Заданы нулевые размеры для resize, такой картинки не бывает
        if ($this->width == 0 && $this->height == 0) {
            $this->exit404();
        }

        // Проверяем являются ли новые размеры разрешёнными
        if (!$this->isAllowResize()) {
            $this->exit404();
        }

        unset($imgInfo[0]);
        /** @var string $imgPath Путь к исходнму изображению */
        $this->fullNameOriginal = $_SERVER['DOCUMENT_ROOT'] . '/' . implode('/', $imgInfo);
        // Проверяем, существует ли исходный файл
        if (!file_exists($this->fullNameOriginal)) {
            $this->exit404();
        }
    }

    /**
     * Проверка, входит ли требуемый размер в списко разрешённых
     *
     * @return bool Истина, в случае наличия требуемого размера в списке разрешённых или отсутствия такого списка
     */
    protected function isAllowResize()
    {
        // Находим путь к конфигурационному файлу админки
        $self = trim($_SERVER['PHP_SELF'], '/');
        $cmsFolder = '/' . substr($self, 0, strpos($self, '/'));
        $path = $_SERVER['DOCUMENT_ROOT'] . $cmsFolder . '/site_data.php';

        if (is_file($path)) {
            /** @var string $path Путь к исходнму изображению */
            $config = include_once($path);
        } else {
            return false;
        }

        // Проверяем существует ли список разрешённых размеров
        if (!isset($config['allowResize'])) {
            return true;
        }

        // Проверяем есть ли в списке разрешённых размеров изображений запрошенное
        $allowResize = explode('\n', $config['allowResize']);
        if (!in_array($this->width . $this->sizeDelimiter . $this->height, $allowResize)) {
            return false;
        }
        return true;
    }

    /**
     * Изменение размера изображения
     *
     * @return mixed Данные изображения
     */
    protected function resizeImage()
    {
        $imageInfo = getimagesize($this->fullNameOriginal);
        $src = null;

        switch ($imageInfo['mime']) {
            case "image/jpeg":
                $src = imagecreatefromjpeg($this->fullNameOriginal);
                break;
            case "image/png":
                $src = imagecreatefrompng($this->fullNameOriginal);
                break;
        }

        // Если тип изображения не соответствует необходимому
        if (is_null($src)) {
            $this->exit404();
        }

        // Пропорциональное изменение изображения по ширине и высоте
        if ($this->width == 0) {
            $this->width = round(($this->height * imagesx($src)) / imagesy($src));
        }
        if ($this->height == 0) {
            $this->height = round(($this->width * imagesy($src)) / imagesx($src));
        }

        // Проверка цвета фона
        $isSetColor = false;
        if (count($this->color) == 3) {
            $isSetColor = true;
        }

        $resDest = $this->width / $this->height;
        $resSrc = imagesx($src) / imagesy($src);
        if ($resDest < $resSrc) {
            $destWidth = round(imagesx($src) * $this->height / imagesy($src));
            $dest2 = $this->imageCreate($this->width, $this->height, $imageInfo['mime']);

            if ($isSetColor) {
                // Изменение размера изображения с добавлением цвета фона
                $destHeight = round(($this->width * imagesy($src)) / imagesx($src));
                $destHeight2 = ($this->height - $destHeight) / 2;
                $bgColor = imagecolorallocate($dest2, $this->color[0], $this->color[1], $this->color[2]);
                imagefill($dest2, 0, 0, $bgColor);
                if ($destWidth > $this->width) {
                    $destWidth = $this->width;
                }
                imageCopyResampled(
                    $dest2,
                    $src,
                    0,
                    $destHeight2,
                    0,
                    0,
                    $destWidth,
                    $destHeight,
                    imagesx($src),
                    imagesy($src)
                );
            } else {
                // Изменение размера изображения и обрезка по ширине
                $dest = $this->imageCreate($destWidth, $this->height, $imageInfo['mime']);
                $destWidth2 = ($destWidth - $this->width) / 2;
                imageCopyResampled($dest, $src, 0, 0, 0, 0, $destWidth, $this->height, imagesx($src), imagesy($src));
                imagecopy($dest2, $dest, 0, 0, $destWidth2, 0, imagesx($dest), imagesy($dest));
            }
        } else {
            $destHeight = round(imagesy($src) * $this->width / imagesx($src));
            $dest2 = $this->imageCreate($this->width, $this->height, $imageInfo['mime']);

            if ($isSetColor) {
                // Изменение размера изображения с добавлением цвета фона
                $destWidth = round(($this->height * imagesx($src)) / imagesy($src));
                $destWidth2 = ($this->width - $destWidth) / 2;
                $bgColor = imagecolorallocate($dest2, $this->color[0], $this->color[1], $this->color[2]);
                imagefill($dest2, 0, 0, $bgColor);
                if ($destHeight > $this->height) {
                    $destHeight = $this->height;
                }
                imageCopyResampled(
                    $dest2,
                    $src,
                    $destWidth2,
                    0,
                    0,
                    0,
                    $destWidth,
                    $destHeight,
                    imagesx($src),
                    imagesy($src)
                );
            } else {
                // Изменение размера изображения и обрезка по высоте
                $dest = $this->imageCreate($this->width, $destHeight, $imageInfo['mime']);
                $destHeight2 = ($destHeight - $this->height) / 2;
                imageCopyResampled($dest, $src, 0, 0, 0, 0, $this->width, $destHeight, imagesx($src), imagesy($src));
                imagecopy($dest2, $dest, 0, 0, 0, $destHeight2, imagesx($dest), imagesy($dest));
                imagedestroy($dest);
            }
        }

        ob_start();
        switch ($imageInfo['mime']) {
            case "image/jpeg":
                imagejpeg($dest2);
                break;
            case "image/png":
                imagepng($dest2);
                break;
        }
        $image = ob_get_contents();
        ob_end_clean();

        // Если не удалось создать изображение
        if ($image == '') {
            $this->exit404();
        }

        return $image;
    }

    /**
     * Создание нового полноцветного изображения
     *
     * @param int $width Ширина нового изображения
     * @param int $height Высота нового изображения
     * @param string $mime Тип файла
     * @return mixed Идентификатор изображения
     */
    protected function imageCreate($width, $height, $mime)
    {
        $img = imagecreatetruecolor($width, $height);
        if ($mime == "image/png") {
            imagecolortransparent($img, imagecolorallocatealpha($img, 0, 0, 0, 127));
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }
        return $img;
    }

    /**
     * @param mixed $rImage Изображение в бинарном виде
     * @param string $originalImagePath Путь к оригиналу изображения
     */
    protected function saveImage($rImage, $originalImagePath)
    {
        /** @var string $pathResizedImg Путь к новому изображению */
        $pathResizedImg = $_SERVER['DOCUMENT_ROOT']
            . '/images/resized/'
            . str_replace('/' . basename($this->fullNameOriginal), '', $originalImagePath);

        /** Добавляем структуру категорий */
        if (!is_dir($pathResizedImg)) {
            mkdir($pathResizedImg, 0777, true);
        }

        $this->fullNameResized = $pathResizedImg . '/' . basename($this->fullNameOriginal);

        file_put_contents($this->fullNameResized, $rImage);
    }

    /**
     * Вывод изображения
     *
     * @param mixed $image Изображение в бинарном виде
     */
    protected function echoImage($image)
    {
        // Получение даты изменения оригинального файла
        $time = filemtime($this->fullNameOriginal);
        touch($this->fullNameResized, $time);

        // Вывод изображения
        $getInfo = getimagesize($this->fullNameResized);
        $time = filemtime($this->fullNameResized);

        header('Content-type: ' . $getInfo['mime']);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $time) . ' GMT');

        echo($image);
    }

    /**
     *  Отправка 404 ошибки
     */
    protected function exit404()
    {
        header("HTTP/1.x 404 Not Found");
        exit;
    }
}
