<?php
// Говорим минимайзеру что не нужно выводить путь до итогового файла
$cssMin = DOCUMENT_ROOT . '/css/min.gen.php';
$jsMin = DOCUMENT_ROOT . '/js/min.gen.php';

updateCreatingInstanceOfClassMinifire($cssMin, '$min = new Minifier(array(\'echo\' => false));');
updateCreatingInstanceOfClassMinifire($jsMin, '$min = new Minifier(array(\'closure\' => false, \'echo\' => false));');

function updateCreatingInstanceOfClassMinifire($filePath, $trueString)
{
    // Проверяем на существование
    if (file_exists($filePath)) {
        if (!is_writable($filePath)) {
            echo 'Файл ' . $filePath . ' недоступен для записи';
        } else {
            // Построково считываем файл
            $cssMinFileContent = '';
            $needReplace = false;
            $cssMinFile = file_get_contents($filePath);
            $cssMinFile = explode("\n", mb_ereg_replace("\r\n", "\n", $cssMinFile));
            foreach ($cssMinFile as $line) {
                // Находим строку в которой создаётся экземпляр класса "Minifire"
                if (mb_strpos($line, 'Minifier') && !mb_strpos($line, 'Library')) {
                    // Удаляем все пробелы из найденной строки, для более точного сравнения
                    $line = str_replace(' ', '', trim($line));
                    $trueStringNoSspaces = str_replace(' ', '', trim($trueString));
                    // Проверяем строку на "правильность"
                    if ($line != $trueStringNoSspaces) {
                        $line = $trueString;
                        $needReplace = true;
                    } else {
                        break;
                    }
                }
                $cssMinFileContent .= $line . "\n";
            }
            if ($needReplace) {
                file_put_contents($filePath, $cssMinFileContent);
            }
        }
    } else {
        echo 'Файла ' . $filePath . ' не существует';
    }
}
