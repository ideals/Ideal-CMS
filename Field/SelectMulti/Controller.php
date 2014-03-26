<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\SelectMulti;

use Ideal\Core\Db;
use Ideal\Field\AbstractController;
use Ideal\Core\Request;

/**
 * Вывод и сохранение select с множественным выбором
 */
class Controller extends AbstractController
{
    protected $list; // Опции списка
    protected static $instance;

    /**
     * Установка модели редактируемого объекта, частью которого является редактируемое поле
     *
     * Полю необходимо получать сведения о состоянии объекта и о других полях, т.к.
     * его значения и поведение может зависеть от значений других полей
     *
     * @param \Ideal\Core\Admin\Model $model Модель редактируемого объекта
     * @param string $fieldName Редактируемое поле
     * @param string $groupName Вкладка, к которой принадлежит редактируемое поле
     */
    public function setModel($model, $fieldName, $groupName = 'general')
    {
        parent::setModel($model, $fieldName, $groupName);

        if (isset($this->field['values'])) {
            // Если значения селекта заданы с помощью массива в поле values
            $this->list = $this->field['values'];
            return;
        }
        // Загоняем в $this->list список значений select
        $className = $this->field['medium'] . '\\Model';
        $medium = new $className();
        $medium->setObj($this->model);
        $medium->setFieldName($fieldName);
        $this->list = $medium->getList();
    }

    /**
     * Возвращает строку, содержащую html-код элементов ввода для редактирования поля
     *
     * @return string html-код элементов ввода
     */
    public function getInputText()
    {
        $html = '<select multiple="multiple" class="form-control" name="' . $this->htmlName .'[]" id="' . $this->htmlName .'">';
        $value = $this->getValue();
        foreach ($this->list as $k => $v) {
            $selected = '';
            if (array_search($k, $value) !== false) {
                $selected = ' selected="selected"';
            }
            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    /**
     * Получение данных, введённых пользователем, их обработка с уведомлением об ошибках ввода
     *
     * @param bool $isCreate Флаг создания или редактирования элемента
     *
     * @return array
     */
    public function parseInputValue($isCreate)
    {
        $this->newValue = $this->pickupNewValue();

        $item = array(
            'fieldName' => $this->htmlName,
            'value' => count($this->newValue),
            'message' => ''
        );

        $sql = strtolower($this->field['sql']);
        if (empty($this->newValue)
            && (strpos($sql, 'not null') !== false)
            && (strpos($sql, 'default') === false)) {
            // Установлен NOT NULL и нет DEFAULT и $value пустое
            $item['message'] = 'необходимо заполнить это поле';
        }
        $className = $this->field['medium'] . '\\Model';
        $medium = new $className();
        $table = $medium->getTable();
        $item['sqlAdd'] = "DELETE FROM {$table} WHERE id_parent = {{ objectId }};";
        foreach($this->newValue as $v){
            $item['sqlAdd'] .= "INSERT INTO {$table} SET id_parent='{{ objectId }}', id_children='{$v}';";
        }

        return $item;
    }

    /**
     * Определение значения этого поля на основании данных из модели
     *
     * В случае, если в модели ещё нет данных, то значение берётся из поля default
     * в настройках структуры (fields) для соответствующего поля
     *
     * @return string
     */
    public function getValue()
    {
        $db = Db::getInstance();
        $ID = $this->model->getPageData();
        $ID = $ID['ID'];
        $className = $this->field['medium'] . '\\Model';
        $medium = new $className();
        $table = $medium->getTable();
        $_sql = "SELECT id_children FROM {$table} WHERE id_parent = '{$ID}'";
        $result = $db->queryArray($_sql);
        $value = array();
        foreach($result as $v){
            $value[] = $v['id_children'];
        }
        return $value;
    }

}
