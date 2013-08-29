<?php
namespace Ideal\Structure\Service\Redirect;

use Ideal\Core\Util;

class RewriteRile
{
    protected $fileName;
    protected $params;

    public function loadFile($fileName)
    {
        $this->params = array();
        $this->fileName = $fileName;
        $file_handle = fopen($this->fileName, "r");
        while (!feof($file_handle)) {
            $line = trim(fgets($file_handle));
            $line = preg_replace("/\s{2,}/", " ", $line);
            if(strlen($line) < 10) continue;
            $line = explode(' ',$line, 4);
            $this->params[] = $line;
        }
        fclose($file_handle);
    }

    public function getCountParam(){
        return count($this->params);
    }


    public function saveFile()
    {
        // Изменяем постоянные настройки сайта
        $fp = fopen($this->fileName, 'w');
        $file = '';
        foreach($_POST['rule'] as $k => $v){
            if(!isset($v['from']) || $v['from'] =='') continue;
            if(!isset($v['on']) || $v['on'] =='') continue;
            if(strnatcasecmp($v['from'], $v['on']) === 0) continue;
            $file .= "RewriteRule {$v['from']} {$v['on']} {$v['rule']}\n";
        }
        if (fwrite($fp, $file)) {
            $this->loadFile($this->fileName);
            print <<<DONE
<script type="text/javascript">
        var text = '<div class="alert alert-block alert-success fade in"><button type="button" class="close" data-dismiss="alert">&times;</button><span class="alert-heading">Редиректы сохранены!</span></div>';
        $("form").prepend(text);
</script>
DONE;

        }
    }

    public function checkUrl($ulr){
        foreach($this->params as $v){
            if(strnatcasecmp($v[1],$ulr)===0){
                // TODO перенаправить по редиректу
            }
        }

    }


    public function showEdit()
    {
        $str = '';
        foreach($this->params as $k => $v){
            $str .= <<<RULE
            <tr>
<td><input class="input span3" type="text" name="rule[{$k}][from]" value="{$v[1]}"></td>
<td><input class="input span3" type="text" name="rule[{$k}][on]" value="{$v[2]}"></td>
<td><input class="input span2" type="text" name="rule[{$k}][rule]" value="{$v[3]}"></td>
<td><button onclick="delLine(this)" type="button" class="btn btn-danger" style="font-size:22px">&times</button></td>
</tr>
RULE;
        }
        $str .= '</tr>';
        return $str;
    }
}
