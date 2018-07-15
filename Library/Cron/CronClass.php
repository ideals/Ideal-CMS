<?php
namespace Cron;

class CronClass
{
    /** @var array Обработанный список задач крона */
    protected $cron = array();

    /** @var string Путь к файлу с задачами крона */
    protected $cronFile;

    /** @var string Адрес, на который будут высылаться уведомления с крона */
    protected $cronEmail;

    /** @var \DateTime Время последнего успешного запуска крона */
    protected $modifyTime;

    /** @var string Путь к файлу с настройками Ideal CMS */
    protected $dataFile;

    /** @var string Корень сайта */
    protected $siteRoot;

    /** @var string Сообщение после тестового запуска скрипта */
    protected $message = '';

    /**
     * CronClass constructor.
     *
     * @param string $siteRoot Корень сайта, относительно которого указываются скрипты в кроне
     * @param string $cronFile Путь к файлу crontab сайта
     * @param string $dataFile Путь к файлу настроек Ideal CMS (для получения координат отправки сообщений с крона)
     */
    public function __construct($siteRoot = '', $cronFile = '', $dataFile = '')
    {
        $this->cronFile = empty($cronFile) ? __DIR__ . '/../../../crontab' : $cronFile;
        $this->dataFile = empty($dataFile) ? __DIR__ . '/../../../site_data.php' : $dataFile;
        $this->siteRoot = empty($siteRoot) ? dirname(dirname(dirname(dirname(__DIR__)))) : $siteRoot;
    }

    /**
     * Делает проверку на доступность файла настроек, правильность заданий в системе и возможность
     * модификации скрипта обработчика крона
     */
    public function testAction()
    {
        $success = true;

        // Проверяем доступность файла настроек для чтения
        if (is_readable($this->dataFile)) {
            $this->message .= "Файл с настройками сайта существует и доступен для чтения\n";
        } else {
            $this->message .= "Файл с настройками сайта {$this->dataFile} не существует или он недоступен для чтения\n";
            $success = false;
        }

        // Проверяем доступность запускаемого файла для изменения его даты
        if (is_writable($this->cronFile)) {
            $this->message .= "Файл \"" . $this->cronFile . "\" позволяет вносить изменения в дату модификации\n";
        } else {
            $this->message .= "Не получается изменить дату модификации файла \"" . $this->cronFile . "\"\n";
            $success = false;
        }

        // Загружаем данные из cron-файла в переменные класса
        try {
            $this->loadCrontab($this->cronFile);
        } catch (\Exception $e) {
            $this->message .= $e->getMessage() . "\n";
            $success = false;
        }

        // Проверяем правильность задач в файле
        if (!$this->testTasks($this->cron)) {
            $success = false;
        }

        return $success;
    }

    /**
     * Проверяет правильность введённых задач
     *
     * @param array $cron Список задач крона
     * @return bool Правильно или нет записаны задачи крона
     */
    public function testTasks($cron)
    {
        $success = true;
        $taskIsset = false;
        $tasks = $currentTasks = '';
        foreach ($cron as $cronTask) {
            list($taskExpression, $fileTask) = $this->parseCronTask($cronTask);

            // Проверяем правильность написания выражения для крона и существование файла для выполнения
            if (\Cron\CronExpression::isValidExpression($taskExpression) !== true) {
                $this->message .= "Неверное выражение \"{$taskExpression}\"\n";
                $success = false;
            }

            if ($fileTask && !is_readable($fileTask)) {
                $this->message .= "Файл \"{$fileTask}\" не существует или он недоступен для чтения\n";
                $success = false;
            } elseif (!$fileTask) {
                $this->message .= "Не задан исполняемый файл для выражения \"{$taskExpression}\"\n";
                $success = false;
            }

            // Получаем дату следующего запуска задачи
            $cronModel = \Cron\CronExpression::factory($taskExpression);
            $nextRunDate = $cronModel->getNextRunDate($this->modifyTime);

            $tasks .= $cronTask . "\nСледующий запуск файла \"{$fileTask}\" назначен на "
                . $nextRunDate->format('d.m.Y H:i:s') . "\n";
            $taskIsset = true;

            // Если дата следующего запуска меньше, либо равна текущей дате, то добавляем задачу на запуск
            $now = new \DateTime();
            if ($nextRunDate <= $now) {
                $currentTasks .= $cronTask . "\n" . $fileTask
                    . " modify: " . $this->modifyTime->format('d.m.Y H:i:s')
                    . " now: " . $now->format('d.m.Y H:i:s')
                    . "\n";
            }
        }

        // Если в задачах из настройках Ideal CMS не обнаружено ошибок, уведомляем об этом
        if ($success && $taskIsset) {
            $this->message .= "В задачах из файла crontab ошибок не обнаружено\n";
        } elseif (!$taskIsset) {
            $this->message .= implode("\n", $cron) . "Пока нет ни одного задания для выполнения\n";
        }

        // Отображение информации о задачах, требующих запуска в данный момент
        if ($currentTasks && $success) {
            $this->message .= "\nЗадачи для запуска в данный момент:\n";
            $this->message .= $currentTasks;
        } elseif ($taskIsset && $success) {
            $this->message .= "\nВ данный момент запуск задач не требуется\n";
        }

        // Отображение информации о запланированных задачах и времени их запуска
        $tasks = $tasks && $success ? "\nЗапланированные задачи:\n" . $tasks : '';
        $this->message .= $tasks . "\n";

        return $success;
    }

    /**
     * Обработка задач крона и запуск нужных задач
     * @throws \Exception
     */
    public function runAction()
    {
        // Загружаем данные из cron-файла
        $this->loadCrontab($this->cronFile);

        // Вычисляем путь до файла "site_data.php" в корне админки
        $dataFileName = stream_resolve_include_path($this->dataFile);

        // Получаем данные настроек системы
        $siteData = require $dataFileName;

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
            list($taskExpression, $fileTask) = $this->parseCronTask($cronTask);
            if (!$taskExpression || !$fileTask) {
                continue;
            }
            // Получаем дату следующего запуска задачи
            $cron = \Cron\CronExpression::factory($taskExpression);
            $nextRunDate = $cron->getNextRunDate($this->modifyTime);
            $now = new \DateTime();

            // Если дата следующего запуска меньше, либо равна текущей дате, то запускаем скрипт
            if ($nextRunDate <= $now) {
                require_once $fileTask;
                break; // Прекращаем цикл выполнения задач, чтобы не произошло наложения задач друг на друга
            }
        }

        // Изменяем дату модификации файла содержащего задачи для крона
        touch($this->cronFile, $now->getTimestamp());
    }

    /**
     * Возвращает сообщения после тестирования cron'а
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
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

        // Если запускаемый скрипт указан относительно корня сайта, то абсолютизируем его
        if ($fileTask && strpos($fileTask, '/') !== 0) {
            $fileTask = $this->siteRoot . '/' . $fileTask;
        }

        $fileTask = trim($fileTask);

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
            $this->cron = $this->parseCrontab(file_get_contents($fileName));
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
     * @param string $cronString Необработанный crontab
     * @return array Обработанный crontab
     */
    public function parseCrontab($cronString)
    {
        $cron = explode(PHP_EOL, $cronString);
        foreach ($cron as $k => $item) {
            $item = trim($item);
            if (empty($item)) {
                // Пропускаем пустые строки
                continue;
            }
            if ($item[0] === '#') {
                // Если это комментарий, то удаляем его из задач
                unset($cron[$k]);
                continue;
            }
            if (stripos($item, 'mailto') !== false) {
                // Если это адрес, извлекаем его
                $arr = explode('=', $item);
                if (empty($arr[1])) {
                    $this->message = 'Некорректно указан почтовый ящик для отправки сообщений';
                }
                $email = trim($arr[1]);
                $this->cronEmail = trim($email, '"\'');
                // Убираем строку с адресом из списка задач
                unset($cron[$k]);
            }
        }
        return $cron;
    }
}
