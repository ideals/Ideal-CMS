<?php
// Подключаем структуру Контактного лица

use Ideal\Core\Config;
use Ideal\Core\Db;

$config = Config::getInstance();
$db = Db::getInstance();

// 1. Если в config.php отсутствует подключение Ideal_ContactPerson - подключаем
$contactPerson = $config->getStructureByName('Ideal_ContactPerson');
if (false == $contactPerson) {
    $contactPersonId = 0;
    foreach ($config->structures as $val) {
        if ($val['ID'] > $contactPersonId) {
            $contactPersonId = $val['ID'];
        }
    }
    $contactPersonId++;
    $add = <<<ADD

        // Подключаем структуру Контактных лиц
        array(
            'ID' => {$contactPersonId},
            'structure' => 'Ideal_ContactPerson',
            'name' => 'Контактное лицо',
            'isShow' => 0,
            'hasTable' => true
        ),
ADD;
    $fileName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/config.php';
    if (!file_exists($fileName)) {
        throw new \Exception('Файл не найден: ' . $fileName);
    }
    $file = file_get_contents($fileName);
    $pos = strrpos($file, ',');
    $file = substr($file, 0, $pos + 1) . $add . substr($file, $pos + 1);
    file_put_contents($fileName, $file);
    $config->loadSettings();
}

// 2. Настраиваем таблицу для Ideal_ContactPerson
$cfg = $config->getStructureByName('Ideal_ContactPerson');
$crmTable = $config->getTableByName('Ideal_Crm');
$contactPersonTable = $config->getTableByName('Ideal_ContactPerson');
$sql = "SHOW TABLES LIKE '{$crmTable}'";
$crmRes = $db->select($sql);
$sql = "SHOW TABLES LIKE '{$contactPersonTable}'";

// Если отсутствует таблица для Ideal_Crm и для Ideal_ContactPerson, то создаем таблицу для Ideal_ContactPerson
$contactPersonRes = $db->select($sql);
if (empty($crmRes) && empty($contactPersonRes)) {
    // Создание таблицы для структуры управления списком заказчиков
    $db->create($contactPersonTable, $cfg['fields']);
} elseif (!empty($crmRes) && empty($contactPersonRes)) {
    // Если есть таблица для Ideal_Crm, то переименовываем её в таблицу для Ideal_ContactPerson
    $sql = 'RENAME TABLE ' . $crmTable . ' TO ' . $contactPersonTable . ';';
    $db->query($sql);

    // Актуализируем таблицу для структуры Ideal_ContactPerson
    $fieldsInfo = $db->select('SHOW COLUMNS FROM ' . $contactPersonTable . ' FROM `' . $config->db['name'] . '`');
    $fields = array();
    array_walk($fieldsInfo, function ($v) use (&$fields) {
        $fields[$v['Field']] = $v['Type'];
    });

    // Удаляем поле 'prev_structure' при его наличии
    if (isset($fields['prev_structure'])) {
        $sql = "ALTER TABLE ' . $contactPersonTable . ' DROP COLUMN `' . prev_structure . '`;";
        $db->query($sql);
    }

    // Обработка поля 'email'
    if (isset($fields['emails']) && !isset($fields['email'])) {
        $sql = "ALTER TABLE {$contactPersonTable} CHANGE emails email text;";
        $db->query($sql);
    } elseif (!isset($fields['emails']) && !isset($fields['email'])) {
        $sql = "ALTER TABLE {$contactPersonTable} ADD email text COMMENT 'Электронный адрес' AFTER `name`;";
        $db->query($sql);
    }

    // Обработка поля 'client_id'
    if (isset($fields['client_ids']) && !isset($fields['client_id'])) {
        $sql = "ALTER TABLE {$contactPersonTable} CHANGE client_ids client_id text;";
        $db->query($sql);
    } elseif (!isset($fields['client_ids']) && !isset($fields['client_id'])) {
        $sql = "ALTER TABLE {$contactPersonTable} ADD client_id text COMMENT 'Client ID' AFTER `email`;";
        $db->query($sql);
    }

    // Обработка поля 'phone'
    if (isset($fields['phones']) && !isset($fields['phone'])) {
        $sql = "ALTER TABLE {$contactPersonTable} CHANGE phones phone text;";
        $db->query($sql);
    } elseif (!isset($fields['phones']) && !isset($fields['phone'])) {
        $sql = "ALTER TABLE {$contactPersonTable} ADD phone text COMMENT 'Телефон' AFTER `client_id`;";
        $db->query($sql);
    }

    // Обработка поля 'lead'
    if (!isset($fields['lead'])) {
        $sql = "ALTER TABLE {$contactPersonTable} ADD lead int(8) COMMENT 'Лид' AFTER `phone`;";
        $db->query($sql);
    }
}
