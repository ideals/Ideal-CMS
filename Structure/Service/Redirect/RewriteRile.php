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
        $tmp = array();
        $tmp[0] = 'RewriteRule';
        $tmp[1] = $_POST['from'];
        $tmp[2] = $_POST['on'];
        $tmp[3] = '[R=301,L]';
        $this->params[] = $tmp;
        unset($tmp);
        foreach($this->params as $k => $v){
            if(!isset($v[1]) || $v[1] =='') continue;
            if(!isset($v[2]) || $v[2] =='') continue;
            if(strnatcasecmp($v[1], $v[2]) === 0) continue;
            $file .= "{$v[0]} {$v[1]} {$v[2]} {$v[3]}\n";
        }
        fwrite($fp, $file);
    }

    public function checkUrl(){
        $url = $_GET['url'];
        foreach($this->params as $v){
            if(strnatcasecmp($v[1],$url)===0){
                // TODO перенаправить по редиректу
            }
        }

    }


    public function showEdit()
    {
        $str = '';
        foreach($this->params as $k => $v){
            $str .= <<<RULE
            <tr id="line{$k}">
<td class="from">{$v[1]}</td><td class="on">{$v[2]}</td>
<td style="text-align: right;">
    <span class="input-prepend">
    <button style="width: 47px;" onclick="editLine({$k})" title="Изменить" class="btn btn-info btn-mini">
    <i class="icon-pencil icon-white"></i></button></span>
    <span class="input-append"><button onclick="delLine({$k})" title="Удалить" class="btn btn-danger btn-mini">
    <i class="icon-remove icon-white"></i></button></span>
</td>
</tr>
RULE;
        }
        $str .= '';
        return $str;
    }
}
