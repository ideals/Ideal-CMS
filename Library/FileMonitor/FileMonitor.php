<?php
namespace FileMonitor;

class FileMonitor
{
    private $files;

    /** @var array Массив со списком файлов, собранный в предыдущий раз */
    private $filesOld = array();

    /** @var string Корень сервера, откуда нужно начинать сканирование файлов */
    private $scanDir = '';

    /** @var array Массив исключений каталогов и файлов. Требуется полный путь до файла/каталога. */
    protected $exclude;

    /** @var string Электронный адрес с которого будут отправляться результаты работы */
    private $from;

    /** @var string Электронные адреса на которые следует отсылать результат работы файла */
    private $to;

    /** @var bool Нужно ли добавлять  */
    private $isFromParameter = true;

    /** @var int Время, отведёное скрипту на выполнение работы в секундах */
    private $scriptTime = 50;

    /** @var int Время начала работы скрипта в формате timestamp */
    private $startTime;

    /** @var array Массив изменённых файлов */
    private $updated = array();

    /** @var array Массив новых файлов */
    private $added = array();

    /** @var array Массив удалённых файлов */
    private $deleted = array();

    /** @var string Доменное имя */
    private $domain;

    /** @var string Периодичность проверки. Варианты: daily, hourly */
    private $period;

    private $fileMonitor = '/file-monitor.txt';
    private $fileMonitorTmp = '/file-monitor-tmp.txt';
    private $fileMonitorUpd = '/file-monitor-upd.txt';

    /**
     * Устанавливает время начала работы скрипта и список файлов/каталогов для исключения из сбора.
     * @throws \Exception
     */
    public function __construct($settings)
    {
        $this->startTime = microtime(true);

        $defaultValues = array(
            'scanDir' => null,
            'tmpDir' => __DIR__,
            'scriptTime' => 50,
            'domain' => null,
            'from' => '',
            'to' => '',
            'exclude' => array(),
            'period' => 'daily',
        );

        $settings = $this->loadCmsSetting($settings);

        $settings['exclude'] = explode("\n", $settings['exclude']);

        // Убираем пустые строки из правил исключения
        $settings['exclude'] = array_filter($settings['exclude']);

        $this->fileMonitor = $settings['tmpDir'] . $this->fileMonitor;
        $this->fileMonitorTmp = $settings['tmpDir'] . $this->fileMonitorTmp;
        $this->fileMonitorUpd = $settings['tmpDir'] . $this->fileMonitorUpd;

        // Игнорируем служебные файлы мониторинга и карту сайта (т.к. в ней ежедневно меняется дата)
        $settings['exclude'][] = '/^' . addcslashes($this->fileMonitor, '/\\') . '/';
        $patternDir = addcslashes($settings['tmpDir'], '/\\');
        $settings['exclude'][] = '/^' . $patternDir . '\/file-reports\/\d\d\d\d-\d\d-\d\d\.txt/';
        $patternDir = rtrim(addcslashes($settings['scanDir'], '/\\'), '/');
        $settings['exclude'][] = '/^' . $patternDir . '\/sitemap\.xml/';

        foreach ($defaultValues as $key => $item) {
            if (empty($settings[$key])) {
                if (null === $item) {
                    throw new \Exception('Не указан обязательный параметр ' . $key);
                }
                $this->$key = $item;
            } else {
                $this->$key = $settings[$key];
            }
        }
    }

    /**
     * Запускает процесс сбора информации всех файлов сайта
     */
    public function scan()
    {
        // Проверка, создан ли файл с хэшами
        if (file_exists($this->fileMonitor)) {
            $time = filemtime($this->fileMonitor);
            if ($this->period === 'daily' && date('d.m.Y') === date('d.m.Y', $time)) {
                echo "Файлы сегодня уже проверялись.\n";
                return;
            }
            if ($this->period === 'hourly' && date('d.m.Y H') === date('d.m.Y H', $time)) {
                echo "Файлы в этот час уже проверялись.\n";
                return;
            }
        }

        // Если существует промежуточный файл с списком пройденных файлов считываем его
        if (file_exists($this->fileMonitorTmp)) {
            $temp = file_get_contents($this->fileMonitorTmp);
            $this->files = unserialize($temp);
            $temp = file_get_contents($this->fileMonitorUpd);
            list($this->updated, $this->added, $this->deleted) = unserialize($temp);
        } else {
            // Если временного файла нет, строим список файлов в $this->files
            $this->glob($this->scanDir);
        }

        // Если существует предыдущий файл
        if (file_exists($this->fileMonitor)) {
            $temp = file_get_contents($this->fileMonitor);
            $this->filesOld = unserialize($temp);
        }

        // Собираем информацию по файлам
        if ($this->parseFiles()) {
            $this->checkDeleted(); // Ищем удалённые файлы
            $this->sendMail(); // Отправляем сообщения и сохраняем результат
        }

        echo 'files: ' . count($this->files) . "\n";
        echo 'updated: ' . count($this->updated)  . "\n";
        echo 'added: ' . count($this->added) . "\n";
        echo 'deleted: ' . count($this->deleted) . "\n";
    }

    /**
     * Считываем все файлы и каталоги из указанного каталога, исключая заданные папки
     *
     * @param string $dir
     */
    private function glob($dir)
    {
        $arr = array_diff(scandir($dir), array('.', '..'));

        foreach ($arr as $v) {
            $file = $dir . '/' . $v;

            foreach ($this->exclude as $pattern) {
                if (preg_match($pattern, $file)) {
                    continue 2;
                }
            }

            if (is_dir($file)) {
                $this->glob($file);
                continue;
            }

            $this->files[$file] = null;
        }
    }

    /**
     * Собирает хэши всех файлов сайта и сортирует их по массивам (новые, изменённые, удалённые)
     */
    private function parseFiles()
    {
        $isTimeOut = false;
        $countOld = count($this->filesOld);

        foreach ($this->files as $file => $hash) {
            if ((microtime(true) - $this->startTime) > $this->scriptTime) {
                $isTimeOut = true;
                break;
            }

            if (null !== $hash) {
                // Файл уже проверен
                continue;
            }

            if (!is_readable($file)) {
                echo 'Файл ' . $file . ' недоступен для чтения';
                continue;
            }

            // todo проверка на наличие adler32, и если нет, то выбираем md5
            $hash = hash_file('adler32', $file);
            $this->files[$file] = $hash;

            if ($countOld == 0) {
                continue;
            }

            if (isset($this->filesOld[$file])) {
                if ($this->filesOld[$file] != $hash) {
                    $this->updated[] = $file;
                }
            } else {
                $this->added[] = $file;
            }
        }

        // Если выходим по таймауту, то сохраняем промежуточнный результат работы скрипта
        if ($isTimeOut) {
            echo 'timeout';
            $this->saveTmpChanges();
        }

        return !$isTimeOut;
    }

    /**
     * Ищет удалённые файлы
     */
    private function checkDeleted()
    {
        if (!empty($this->filesOld)) {
            foreach ($this->filesOld as $k => $v) {
                if (!isset($this->files[$k])) {
                    $this->deleted[] = $k;
                }
            }
        }
    }

    /**
     * Отправляет результат мониторинга файлов на почту, указанную в свойстке "to" этого класса
     */
    private function sendMail()
    {
        $message = '';
        $headers = "From: " . $this->from . "\n"
            . "MIME-Version: 1.0\n"
            . "Content-type: text/plain; charset=UTF-8\n";
        if (count($this->updated) > 0 || count($this->added) > 0 || count($this->deleted) > 0) {
            $message .= 'Всего файлов ' . count($this->files) . "\n\n";
            if (count($this->updated) > 0) {
                $message .= "Изменённые файлы:\n" . implode("\n", $this->updated) . "\n\n";
            }
            if (count($this->added) > 0) {
                $message .= "Добавленные файлы:\n" . implode("\n", $this->added) . "\n\n";
            }
            if (count($this->deleted) > 0) {
                $message .= "Удалённые файлы:\n" . implode("\n", $this->deleted) . "\n\n";
            }
            $params = $this->isFromParameter ? '-f ' . $this->from : null;
            mail($this->to, $this->domain . ': обнаружены изменения в файлах', $message, $headers, $params);
        }
        $this->saveChanges($message);
        $this->delTempFiles();
    }

    /**
     * Сохраняет результат работы скрипта в файлы
     *
     * @param string $message Текст отчёта о работе скрипта
     */
    private function saveChanges($message)
    {
        // Записываем в файл результат обхода
        $a = serialize($this->files);
        $fp = fopen($this->fileMonitor, 'w+');
        fwrite($fp, $a);
        fclose($fp);

        // Записываем в файл текст отчёта
        if (!empty($message)) {
            $fileMonitorReporstDir = dirname($this->fileMonitor) . '/file-reports/';
            if (!file_exists($fileMonitorReporstDir)) {
                mkdir($fileMonitorReporstDir);
            }
            $reportFileName = $fileMonitorReporstDir . date('Y-m-d') . '.txt';
            $fp = fopen($reportFileName, 'w+');
            fwrite($fp, $message);
            fclose($fp);
        }
    }

    /**
     * Сохраняет промежуточный результат работы скрипта
     */
    private function saveTmpChanges()
    {
        $a = serialize($this->files);
        $fp = fopen($this->fileMonitorTmp, 'w+');
        fwrite($fp, $a);
        fclose($fp);

        $a = serialize(array($this->updated, $this->added, $this->deleted));
        $fp = fopen($this->fileMonitorUpd, 'w+');
        fwrite($fp, $a);
        fclose($fp);
    }

    /**
     * Удаляет временные файлы сканирования
     */
    private function delTempFiles()
    {
        if (file_exists($this->fileMonitorTmp)) {
            unlink($this->fileMonitorTmp);
        }
        if (file_exists($this->fileMonitorUpd)) {
            unlink($this->fileMonitorUpd);
        }
    }

    /**
     * Загрузка настроек из файла конфигурации Ideal CMS
     *
     * Если настройки Ideal CMS не удалось обнаружить, то они не применятся, но и ошибки в этом не будет.
     *
     * @param array $settings
     * @return array
     */
    private function loadCmsSetting($settings)
    {
        $dataFile = __DIR__ . '/../../../site_data.php';
        if ($dataFile = stream_resolve_include_path($dataFile)) {
            $data = require $dataFile;
            $scanDir = $data['monitoring']['scanDir'];
            $settings['scanDir'] = stream_resolve_include_path(
                empty($scanDir) ? $settings['scanDir'] : $scanDir
            );
            $tmpDir = $settings['scanDir'] . $data['cms']['tmpFolder'];
            $settings['tmpDir'] = empty($tmpDir) ? $settings['tmpDir'] : $tmpDir;
            $settings['exclude'] = $data['monitoring']['exclude'];
            $settings['to'] = $data['cms']['adminEmail'];
            $settings['from'] = $data['robotEmail'];
            $settings['domain'] = $data['domain'];
        }
        return $settings;
    }
}
