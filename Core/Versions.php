<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

class Versions
{
    /**
     * Получение версии админки, а также наименований модулей и их версий
     *
     * @return array Массив с номерами установленных версий
     *
     * @throws \RuntimeException
     */
    public function getVersions(): array
    {
        $config = Config::getInstance();
        // Путь к файлу README.md для cms
        $mods['Ideal-CMS'] = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal';

        // Ищем файлы README.md в модулях
        $modDirName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Mods';
        if (file_exists($modDirName)) {
            // Получаем папки
            $modDirs = array_diff(scandir($modDirName), ['.', '..']); // получаем массив папок модулей
            foreach ($modDirs as $dir) {
                // Исключаем папки, явно не содержащие модули
                if ((stripos($dir, '.') === 0) || (is_file($modDirName . '/' . $dir))) {
                    unset($mods[$dir]);
                    continue;
                }

                $mods[$dir] = $modDirName . '/' . $dir;
            }
        }

        return $this->getVersionFromReadme($mods);
    }

    /**
     * Получение версий из Readme.md
     *
     * @param array<string, string> $mods Массив состоящий из названий модулей и полных путей к ним
     *
     * @return array Версии модулей или false в случае ошибки
     *
     * @throws \RuntimeException
     */
    public function getVersionFromReadme(array $mods): array
    {
        // Получаем файл README.md для cms
        $mdFile = 'README.md';
        $version = [];
        foreach ($mods as $k => $v) {
            if (!file_exists($v . '/' . $mdFile)) {
                throw new \RuntimeException('Отсутствует файл ' . $v . '/' . $mdFile);
            }

            $lines = file($v . '/' . $mdFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_bool($lines) || $lines === []) {
                throw new \RuntimeException('Не удалось получить версию из ' . $v . '/' . $mdFile);
            }

            // Получаем номер версии из первой строки
            $firstLine = reset($lines);
            // Формат номера: пробел+v.+пробел+номер-версии+пробел-или-конец-строки
            preg_match_all('/\sv\.(\s*)(.*)(\s*)/i', $firstLine, $ver);
            // Если номер версии не удалось определить — выходим
            if (!isset($ver[2][0]) || $ver[2][0] === '') {
                throw new \RuntimeException('Ошибка при разборе строки с версией файла: ' . $firstLine);
            }

            $version[$k] = $ver[2][0];
        }

        return $version;
    }
}
