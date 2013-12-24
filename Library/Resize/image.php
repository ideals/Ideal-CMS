<?php

$error = true;
if ($_GET['img'] != '') {
    $imgInfo = explode('/', $_GET['img']);
    $imgSize = explode('x', $imgInfo[0]);
    $imgName = end($imgInfo);
    $countImgSize = count($imgSize);
    if ($countImgSize == 2 || $countImgSize == 3) {
        unset($imgInfo[0]);
        $width = intval($imgSize[0]);
        $height = intval($imgSize[1]);
        $imgPath = $_SERVER['DOCUMENT_ROOT'] . '/' . implode('/', $imgInfo);
        $resizedImgPath = $_SERVER['DOCUMENT_ROOT']
                        . '/images/resized/'
                        . str_replace('/' . $imgName, '', $_GET['img']);
        $color = array();
        if (isset($imgSize[2])) {
            // Преобразование цвета фона
            $color = sscanf('#' . $imgSize[2], '#%2x%2x%2x');
        }
        if (file_exists($imgPath)) {
            resizeImg($imgPath, $width, $height, $resizedImgPath, $imgName, $color);
            $error = false;
        }
    }
}

if ($error) {
    header("HTTP/1.x 404 Not Found");
}

/**
 * Изменение размеров изображения
 * @param string $imgPath Путь к Исходнму изображению
 * @param int $width Ширина нового изображения
 * @param int $height Высота нового изображения
 * @param string $resizedImgPath Путь к новому изображению
 * @param string $imgName Имя нового изображения
 * @param array $color Цвет фона изображения,
 * необходимо указывать чтобы ширина и высота нового изображения являлись максимальными его значениями
 * и не обрезали изображение,
 * а также пространство добавляемое для изображения, окрашивалось указанным цветом
 * @return void
 */
function resizeImg($imgPath, $width, $height, $resizedImgPath, $imgName, $color)
{
    $src = imagecreatefromjpeg($imgPath);
    if ($width == 0 && $height == 0) {
        exit;
    }
    // Пропорциональное изменение изображения по ширине и высоте
    if ($width == 0) {
        $width = round(($height * imagesx($src)) / imagesy($src));
    }
    if ($height == 0) {
        $height = round(($width * imagesy($src)) / imagesx($src));
    }
    // Проверка цвета фона
    $isSetColor = false;
    if (count($color) == 3) {
        $isSetColor = true;
    }
    $resDest = $width / $height;
    $resSrc = imagesx($src) / imagesy($src);
    if ($resDest < $resSrc) {
        $destWidth = round(imagesx($src) * $height / imagesy($src));
        $dest2 = imagecreatetruecolor($width, $height);

        if ($isSetColor) {
            // Изменение размера изображения с добавлением цвета фона
            $destHeight = round(($width * imagesy($src)) / imagesx($src));
            $destHeight2 = ($height - $destHeight) / 2;
            $bgColor = imagecolorallocate($dest2, $color[0], $color[1], $color[2]);
            imagefill($dest2, 0, 0, $bgColor);
            if ($destWidth > $width) {
                $destWidth = $width;
            }
            imageCopyResampled($dest2, $src, 0, $destHeight2, 0, 0, $destWidth, $destHeight, imagesx($src), imagesy($src));
        } else {
            // Изменение размера изображения и обрезка по ширине
            $dest = imagecreatetruecolor($destWidth, $height);
            $destWidth2 = ($destWidth - $width) / 2;
            imageCopyResampled($dest, $src, 0, 0, 0, 0, $destWidth, $height, imagesx($src), imagesy($src));
            imagecopy($dest2, $dest, 0, 0, $destWidth2, 0, imagesx($dest), imagesy($dest));
        }
    } else {
        $destHeight = round(imagesy($src) * $width / imagesx($src));
        $dest2 = imagecreatetruecolor($width, $height);

        if ($isSetColor) {
            // Изменение размера изображения с добавлением цвета фона
            $destWidth = round(($height * imagesx($src)) / imagesy($src));
            $destWidth2 = ($width - $destWidth) / 2;
            $bgColor = imagecolorallocate($dest2, $color[0], $color[1], $color[2]);
            imagefill($dest2, 0, 0, $bgColor);
            if ($destHeight > $height) {
                $destHeight = $height;
            }
            imageCopyResampled($dest2, $src, $destWidth2, 0, 0, 0, $destWidth, $destHeight, imagesx($src), imagesy($src));
        } else {
            // Изменение размера изображения и обрезка по высоте
            $dest = imagecreatetruecolor($width, $destHeight);
            $destHeight2 = ($destHeight - $height) / 2;
            imageCopyResampled($dest, $src, 0, 0, 0, 0, $width, $destHeight, imagesx($src), imagesy($src));
            imagecopy($dest2, $dest, 0, 0, 0, $destHeight2, imagesx($dest), imagesy($dest));
        }
    }

    // Добавление структуры директорий
    if (!is_dir($resizedImgPath)) {
        mkdir($resizedImgPath, 0777, true);
    }

    // Сохранение изображения
    imagejpeg($dest2, $resizedImgPath . '/' . $imgName);

    // Получение даты изменения файла
    if (file_exists($resizedImgPath . '/' . $imgName)) {
        $time = filemtime($resizedImgPath . '/' . $imgName);
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $time) . ' GMT');
    }

    // Вывод изображения
    header('Content-Type: image/jpg');
    imagejpeg($dest2);



    imagedestroy($dest2);
    imagedestroy($dest);
    imagedestroy($src);
}
