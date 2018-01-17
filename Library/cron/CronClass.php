<?php
namespace Cron;

use Ideal\Core\Config;

class CronClass
{
    protected $cron = array();
    protected $cronFile;
    protected $cronEmail;
    protected $modifyTime;
    protected $dataFile;

    /**@var string Тип запуска web или cli*/
    protected $type = 'cli';

    /**@var string Обозначение конца строки PHP_EOL для cli или <br /> для web*/
    protected $stringEol = "\n";

    /**
     * CronClass constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->cronFile = __DIR__ . '/../../../crontab';
        $this->dataFile = __DIR__ . '/../../../site_data.php';
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
        if ($type == 'web') {
            $this->stringEol = '<br />';
        }
    }

    /**
     * Обработчик автозагрузки для библиотеки cron-expression
     *
     * @param string $className Имя класса, которое не нашлось в пространстве имён
     * @return bool Флаг успешного подключения файла класса
     */
    public static function autoloader($className)
    {
        $className = ltrim($className, '\\');

        $elements = explode('\\', $className);
        $file = array_pop($elements); // убираем последний элемент массива — имя файла

        $folder = implode(DIRECTORY_SEPARATOR, $elements);
        $fileName = $folder . DIRECTORY_SEPARATOR . $file . '.php';

        $config = Config::getInstance();
        set_include_path(
            get_include_path()
            . PATH_SEPARATOR . DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal/Library/cron-expression/src/'
        );

        if (stream_resolve_include_path($fileName) !== false) {
            require_once $fileName;
            return true;
        } else {
            // Если файл не удалось подключить — обработка уйдёт дальше по стеку автозагрузки
            return false;
        }
    }

    /**
     * Делает проверку на доступность файла настроек, правильность заданий в системе и возможность
     * модификации скрипта обработчика крона
     */
    public function testAction()
    {
        $response = '';
        // Проверяем доступность файла настроек для чтения
        if (is_readable($this->dataFile)) {
            $response .= "Файл с настройками сайта существует и доступен для чтения{$this->stringEol}";
        } else {
            $response .= "Файла с настройками сайта {$this->dataFile} не существует или он недоступен для чтения{$this->stringEol}";
        }

        // Вычисляем путь до файла $this->cronFile в корне админки для использования в уведомлениях
        $cronFilePathParts = explode('/', $this->cronFile);
        $countDirUp = 0;
        foreach ($cronFilePathParts as $key => $part) {
            if ($part == '..') {
                $countDirUp++;
                unset($cronFilePathParts[$key]);
            }
        }
        $file = array_pop($cronFilePathParts);
        $cronFilePathParts = array_slice($cronFilePathParts, 0, count($cronFilePathParts) - $countDirUp);
        $cronFilePath = implode('/', $cronFilePathParts) . '/' . $file;

        // Проверяем доступность запускаемого файла для изменения его даты
        if (is_writable($this->cronFile)) {
            $response .= "Файл \"" . $cronFilePath . "\" позволяет вносить изменения в дату модификации{$this->stringEol}";
        } else {
            $response .= "Не получается изменить дату модификации файла \"" . $cronFilePath . "\"{$this->stringEol}";
        }

        // Загружаем данные из cron-файла
        $this->loadCrontab($this->cronFile);

        $failure = false;
        $taskIsset = false;
        $tasks = $currentTasks = '';
        foreach ($this->cron as $cronTask) {
            if ($cronTask) {
                list($taskExpression, $fileTask) = $this->parseCronTask($cronTask);
                $fileTask = trim($fileTask);
                // Проверяем правильность написания выражения для крона и существование файла для выполнения
                if (CronExpression::isValidExpression($taskExpression) !== true) {
                    $response .= "Неверное выражение \"{$taskExpression}\"{$this->stringEol}";
                    $failure = true;
                }
                // Если запускаемый скрипт указан относительно корня сайта, то абсолютизируем его
                if ($fileTask && strpos($fileTask, '/') !== 0) {
                    $fileTask = DOCUMENT_ROOT . '/' .$fileTask;
                }
                if ($fileTask && !is_readable($fileTask)) {
                    $response .= "Файла \"{$fileTask}\" не существует или он недоступен для чтения{$this->stringEol}";
                    $failure = true;
                } elseif (!$fileTask) {
                    $response .= "Не задан исполняемый файл для выражения \"{$taskExpression}\"{$this->stringEol}";
                    $failure = true;
                }

                // Получаем дату следующего запуска задачи
                $cron = CronExpression::factory($taskExpression);
                $nextRunDate = $cron->getNextRunDate($this->modifyTime);

                $tasks .= $cronTask . "{$this->stringEol}Следующий запуск файла \"{$fileTask}\" назначен на "
                    . $nextRunDate->format('d.m.Y H:i:s') . "{$this->stringEol}";
                $taskIsset = true;

                // Если дата следующего запуска меньше, либо равна текущей дате, то добавляем задачу на запуск
                $now = new \DateTime();
                if ($nextRunDate <= $now) {
                    $currentTasks .= $cronTask . "\n" . $fileTask
                        . " modify: " . $this->modifyTime->format('d.m.Y H:i:s')
                        . " now: " . $now->format('d.m.Y H:i:s')
                        . "{$this->stringEol}";
                }
            }
        }

        // Если в задачах из настройках Ideal CMS не обнаружено ошибок, уведомляем об этом
        if (!$failure && $taskIsset) {
            $response .= "В задачах из настроек Ideal CMS ошибок не обнаружено{$this->stringEol}";
        } elseif (!$taskIsset) {
            $response .= "Пока нет ни одного задания для выполнения{$this->stringEol}";
        }

        // Отображение информации о задачах, требующих запуска в данный момент
        if ($currentTasks) {
            $response .= "\nЗадачи для запуска в данный момент:{$this->stringEol}";
            $response .= $currentTasks;
        } elseif ($taskIsset) {
            $response .= "\nВ данный момент запуск задач не требуется{$this->stringEol}";
        }

        // Отображение информации о запланированных задачах и времени их запуска
        $tasks = $tasks ? "{$this->stringEol}Запланированные задачи:{$this->stringEol}" . $tasks : '';
        $response .= $tasks . "{$this->stringEol}";
        return $response;
    }

    /**
     * Обработка задач крона и запуск нужных задач
     * @return string Текстовый вывод обработчика задач крона и выполненных задач
     * @throws \Exception
     */
    public function runAction()
    {
        // Загружаем данные из cron-файла
        $this->loadCrontab($this->cronFile);

        // Получаем данные настроек системы
        $siteData = require $this->dataFile;

        $data = array(
            'domain' => $siteData['domain'],
            'robotEmail' => $siteData['robotEmail'],
            'adminEmail' => $this->cronEmail ? $this->cronEmail : $siteData['cms']['adminEmail'],
        );

        // Переопределяем стандартный обработчик ошибок для отправки уведомлений на почту
        set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($data) {

            // Формируем текст ошибки
            $_err = 'Ошибка [' . $errno . '] ' . $errstr . ', в строке ' . $errline . ' файла ' . $errfile;

            // Выводим текст ошибки
            echo $_err . "\n";

            // Формируем заголовки письма и отправляем уведомление об ошибке на почту ответственного лица
            $header = "From: \"{$data['domain']}\" <{$data['robotEmail']}>\r\n";
            $header .= "Content-type: text/plain; charset=\"utf-8\"";
            mail($data['adminEmail'], 'Ошибка при выполнении крона', $_err, $header);
        });


        // Обрабатываем задачи для крона из настроек Ideal CMS
        $now = new \DateTime();
        foreach ($this->cron as $cronTask) {
            if ($cronTask) {
                list($taskExpression, $fileTask) = $this->parseCronTask($cronTask);
                if ($taskExpression && $fileTask) {
                    // Получаем дату следующего запуска задачи
                    $cron = \Cron\CronExpression::factory($taskExpression);
                    $nextRunDate = $cron->getNextRunDate($this->modifyTime);
                    $now = new \DateTime();

                    // Если дата следующего запуска меньше, либо равна текущей дате, то запускаем скрипт
                    if ($nextRunDate <= $now) {
                        $fileTask = trim($fileTask);
                        require_once $fileTask;
                    }
                }
            }
        }

        // Изменяем дату модификации файла содержащего задачи для крона
        touch($this->cronFile, $now->getTimestamp());

        return '';
    }

    /**
     * Разбирает задачу для крона из настроек Ideal CMS
     * @param string $cronTask Строка в формате "* * * * * /path/to/file.php"
     * @return array Массив, где первым элементом является строка соответствующая интервалу запуска задачи по крону,
     *               а вторым элементом является путь до запускаемого файла
     */
    protected function parseCronTask($cronTask)
    {
        // Получаем cron-формат запуска файла в первых пяти элементах массива и путь до файла в последнем элементе
        $taskParts = explode(' ', $cronTask, 6);
        $fileTask = '';
        if (count($taskParts) >= 6) {
            $fileTask = array_pop($taskParts);
        }

        $taskExpression = implode(' ', $taskParts);
        return array($taskExpression, $fileTask);
    }

    /**
     * Загружаем данные из крона в переменные cron, cronEmail, modifyTime
     *
     * @throws \Exception
     */
    private function loadCrontab($fileName)
    {
        $fileName = stream_resolve_include_path($fileName);
        if ($fileName) {
            $this->cron = explode(PHP_EOL, file_get_contents($fileName));
            $this->cronEmail = $this->extractEmail();
        } else {
            $this->cron = array();
            $fileName = stream_resolve_include_path(dirname($fileName)) . 'crontab';
            file_put_contents($fileName, '');
        }
        $this->cronFile = $fileName;

        // Получаем дату модификации скрипта (она же считается датой последнего запуска)
        $this->modifyTime = new \DateTime();
        $this->modifyTime->setTimestamp(filemtime($fileName));
    }

    /**
     * Извлечение почтового адреса для отправки уведомлений с крона. Формат MAILTO="email@email.com"
     *
     * @return string Email-адрес
     * @throws \Exception
     */
    private function extractEmail()
    {
        $email = '';
        foreach ($this->cron as $k => $item) {
            $item = trim($item);
            if ($item[0] == '#') {
                // Если это комментарий, то удаляем его из задач
                unset($this->cron[$k]);
                continue;
            }
            if (strpos(strtolower($item), 'mailto') !== false) {
                // Если это адрес, извлекаем его
                $arr = explode('=', $item);
                if (empty($arr[1])) {
                    throw new \Exception('Не указан почтовый ящик для отправки сообщений');
                }
                $email = trim($arr[1]);
                $email = trim($email, '"\'');
                // Убираем строку с адресом из списка задач
                unset($this->cron[$k]);
            }
        }
        return $email;
    }
}
