<?php
namespace Ideal\Structure\Service\SiteData;

use Ideal\Core\Util;

class ConfigPhp
{
    protected $fileName;
    protected $params;

    public function loadFile($fileName)
    {
        $this->fileName = $fileName;
        $cfg = file($fileName);
        $skip = array('<?php', 'return array(', ');');

        foreach ($cfg as $k => $v) {
            if (in_array(trim($v), $skip)) {
                continue;
            }
            list ($other, $label) = explode("', // ", $v);
            $label = chop($label);
            list($label, $type) = explode(' | ', $label);
            if ($type == '') {
                $type = 'Ideal_Text';
            }
            list ($name, $value) = explode(" => '", $other);
            $fieldName = trim(substr($name, 5), '\'');
            $this->params[$fieldName] = array(
                'label' => $label,
                'value' => $value,
                'type' => $type
            );
        }
    }


    public function saveFile()
    {
        // Изменяем постоянные настройки сайта
        $fp = fopen($this->fileName, 'w');
        $file = "<?php\nreturn array(\n";
        foreach ($this->params as $fieldName => $param) {
            $model = new mockModel();
            $model->fields[$fieldName] = array('label' => $param['label']);
            $model->object[$fieldName] = $param['value'];

            $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
            /** @var $fieldModel \Ideal\Field\AbstractController */
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($model, $fieldName, 'general');

            $value = $fieldModel->pickupNewValue();

            $file .= "    '" . $fieldName . "' => '" . $value
                . "', // " . $param['label'] . ' | ' . $param['type'] . "\n";
        }
        $file .= ");\n";
        if (fwrite($fp, $file)) {
            $this->loadFile($this->fileName);
            print <<<DONE
<script type="text/javascript">
        var text = '<div class="alert alert-block alert-success fade in"><button type="button" class="close" data-dismiss="alert">&times;</button><span class="alert-heading">Настройки сохранены!</span></div>';
        $("form").prepend(text);
</script>
DONE;

        }
    }


    public function showEdit()
    {
        $str = '';
        foreach ($this->params as $fieldName => $param) {
            $model = new mockModel();
            $model->fields[$fieldName] = array('label' => $param['label']);
            $model->object[$fieldName] = $param['value'];

            $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($model, $fieldName, 'general');
            $str .= $fieldModel->showEdit();
        }
        return $str;
    }
}


class mockModel
{
    public $object;
    public $fields;
}
