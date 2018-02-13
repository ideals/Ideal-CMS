<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Pos;

use Ideal\Core\Db;

/**
 * Модель для работы с полем сортировки
 */
class Model
{
    /**
     * Получение нового номера pos в списке элементов, на единицу большего, чем существующий
     *
     * @param \Ideal\Core\Model $model
     * @return int
     */
    public function getNewPos($model)
    {
        $db = Db::getInstance();

        $table = $model->getTableName();
        $prevStructure = $model->getPrevStructure();

        $_sql = "SELECT pos FROM {$table} WHERE prev_structure='{$prevStructure}' ORDER BY pos DESC LIMIT 1";
        $posArr = $db->select($_sql);

        $pos = 0;
        if (count($posArr) > 0) {
            // Если элементы на этом уровне есть, берём cid последнего
            $pos = $posArr[0]['pos'];
        }

        // Прибавляем единицу в pos
        return $pos + 1;
    }

    /**
     * Изменение позиции $oldPos на новую $newPos
     *
     * @param int    $oldPos        Старое значение позиции
     * @param int    $newPos        Новое значение позиции
     * @param string $prevStructure Путь к структуре в которой меняются позиции
     * @return string Sql-запрос изменения позиции
     */
    public function movePos($oldPos, $newPos, $prevStructure)
    {
        $update = array($oldPos => $newPos);

        // Определяем реальное значение сегмента в новом cid
        // если cid становится больше, то новое значение уменьшается на единицу,
        // если меньше, то новое значение остаётся прежним
        if ($newPos > $oldPos) {
            for ($i = $oldPos + 1; $i < $newPos + 1; $i++) {
                $update[$i] = $i - 1;
            }
        } else {
            for ($i = $newPos; $i < $oldPos; $i++) {
                $update[$i] = $i + 1;
            }
        }

        $_sql = 'UPDATE {{ table }} SET pos = CASE';
        $where = $or = '';
        foreach ($update as $old => $new) {
            $_sql .= "\nWHEN pos = {$old} THEN {$new}";
            $where .= $or . " pos = {$old}";
            $or = ' OR';
        }
        $_sql .= "\n ELSE pos END WHERE prev_structure='{$prevStructure}' AND ({$where})";
        // На основании массива $update составляем список запросов для обновления cid'ов
        return $_sql;
    }
}
