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
            $fields = explode(' | ', $label);
            $label = $fields[0];
            $type = $fields[1];
            if ($type == '') {
                $type = 'Ideal_Text';
            }
            list ($name, $value) = explode(" => '", $other);
            $fieldName = trim(substr($name, 5), '\'');
            $this->params[$fieldName] = array(
                'label' => $label,
                'value' => ($type == 'Ideal_Area') ? str_replace('\n', "\n", $value) : $value,
                'type' => $type
            );
            if ($type == 'Ideal_Select') {
                $this->params[$fieldName]['values'] = json_decode($fields[2]);
            }
        }
    }


    public function saveFile()
    {
        // Изменяем постоянные настройки сайта
        $file = "<?php\nreturn array(\n";
        foreach ($this->params as $fieldName => $param) {
            $model = new mockModel();
            $model->fields[$fieldName] = $param;
            $model->pageData[$fieldName] = $param['value'];

            $fieldClass = Util::getClassName($param['type'], 'Field') . '\\Controller';
            /** @var $fieldModel \Ideal\Field\AbstractController */
            $fieldModel = $fieldClass::getInstance();
            $fieldModel->setModel($model, $fieldName, 'general');

            $value = $fieldModel->pickupNewValue();
            if ($param['type'] == 'Ideal_Area') {
                $value = str_replace("\r", '', $value);
                $value = str_replace("\n", '\n', $value);
            }

            $options = (defined('JSON_UNESCAPED_UNICODE')) ? JSON_UNESCAPED_UNICODE : 0;
            $values = ($param['type'] == 'Ideal_Select') ? ' | ' . json_encode($param['values'], $options) : '';

            $file .= "    '" . $fieldName . "' => '" . $value
                . "', // " . $param['label'] . ' | ' . $param['type'] . $values . "\n";
        }
        $file .= ");\n";
        $fp = fopen($this->fileName, 'w');
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
            $model->fields[$fieldName] = $param;
            $model->pageData[$fieldName] = $param['value'];

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
    public $pageData;
    public $fields;

    public function getPageData()
    {
        return $this->pageData;
    }

}
