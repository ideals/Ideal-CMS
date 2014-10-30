<?php
/**
 * Скрипт изменения размеров изображения. Вызывается с помощью .htaccess
 */
if (!isset($_GET['img']) || $_GET['img'] == '') {
    // Если не указан параметр содержащий адрес оригинального изображения
    header("HTTP/1.x 404 Not Found");
    exit;
}

$imgInfo = explode('/', $_GET['img']);

// ШАГ 1. Проверяем, соответствует ли запрошенный resize допустимым вариантам,
// заданным в конфиге site_data.php

// Находим путь к конфигурационному файлу админки
$self = trim($_SERVER['PHP_SELF'], '/');
$cmsFolder = '/' . substr($self, 0, strpos($self, '/'));
$path = $_SERVER['DOCUMENT_ROOT'] . $cmsFolder . '/site_data.php';
$config = include_once($path);

if (isset($config['allowResize'])) {
    // Если есть поле allowResize то проверяем, есть ли в нём затребованное правило изменения изображения
    $allowResize = explode('\n', $config['allowResize']);
    if (!in_array($imgInfo[0], $allowResize)) {
        // Правила нет - шлём 404 ошибку
        header("HTTP/1.x 404 Not Found");
        exit;
    }
}

// ШАГ 2. Проверяем формат запрошенного правила resize

$imgSize = explode('x', $imgInfo[0]);
/** @var string $imgName Имя нового изображения */
$imgName = end($imgInfo);
$countImgSize = count($imgSize);
if ($countImgSize != 2 && $countImgSize != 3) {
    // Формат правила неправильный, шлём 404
    header("HTTP/1.x 404 Not Found");
    exit;
}

// ШАГ 3. Проверяем наличие оригинальной картинки

unset($imgInfo[0]);
/** @var string $imgPath Путь к исходнму изображению */
$imgPath = $_SERVER['DOCUMENT_ROOT'] . '/' . implode('/', $imgInfo);
if (!file_exists($imgPath)) {
    // Оригинального файла нет, шлём 404
    header("HTTP/1.x 404 Not Found");
    exit;
}

// ШАГ 4. Подготавливаем параметры для преобразования картинки

/** @var int $width Ширина нового изображения */
$width = intval($imgSize[0]);

/** @var int $height Высота нового изображения */
$height = intval($imgSize[1]);

if ($width == 0 && $height == 0) {
    // Заданы нулевые размеры для resize, такой картинки не бывает
    header("HTTP/1.x 404 Not Found");
    exit;
}

/** @var string $resizedImgPath Путь к новому изображению */
$resizedImgPath = $_SERVER['DOCUMENT_ROOT']
    . '/images/resized/'
    . str_replace('/' . $imgName, '', $_GET['img']);

/** @var array $color Цвет фона изображения */
$color = array();
if (isset($imgSize[2])) {
    // Преобразование цвета фона
    $color = sscanf('#' . $imgSize[2], '#%2x%2x%2x');
}

// ШАГ 5. Изменение размеров изображения

$src = imagecreatefromjpeg($imgPath);

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
        imagedestroy($dest);
    }
}

// Добавление структуры директорий
if (!is_dir($resizedImgPath)) {
    mkdir($resizedImgPath, 0777, true);
}

// Сохранение изображения
imagejpeg($dest2, $resizedImgPath . '/' . $imgName);

// Получение даты изменения оригинального файла
$time = filemtime($imgPath);
touch($resizedImgPath . '/' . $imgName, $time);

// Вывод изображения
header('Content-Type: image/jpg');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $time) . ' GMT');
imagejpeg($dest2);

imagedestroy($dest2);
imagedestroy($src);
