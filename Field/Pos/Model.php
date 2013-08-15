<?php
namespace Ideal\Field\Pos;

class Model
{
    /**
     * Изменение позиции $oldPos на новую $newPos
     * @param $oldPos старое значение позиции
     * @param $newPos новое значение позиции
     * @param $structurePath путь к структуре в которой меняются позиции
     * @return string sql-запрос изменения позиции
     */
    public function movePos($oldPos, $newPos, $structurePath)
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
        $where = $or ='';
        foreach ($update as $old => $new) {
            $_sql .= "\nWHEN pos = {$old} THEN {$new}";
            $where .= $or . " pos = {$old}";
            $or = ' OR';
        }
        $_sql .= "\n ELSE pos END WHERE structure_path='{$structurePath}' AND ({$where})";
        // На основании массива $update составляем список запросов для обновления cid'ов
        return $_sql;
    }
}