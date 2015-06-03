<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\ImageGallery;

class Model
{
    public function getImageList($imageList = array())
    {
        // TODO учесть что вкладок с фотогалереей теоретически может быть несколько
        $fieldList = '
            <script type="text/javascript">
            $("#sortable").sortable({
                placeholder: "ui-state-highlight",
                change: function( event, ui ) {
                    alert(window.idObject[\'fieldName\']);
                }
            });
            $("#sortable").disableSelection();
        </script>';
        $fieldList .= '<ul id="sortable">';
        foreach ($imageList as $key => $value) {
            $fieldList .= '<li class="ui-state-default">'
                . '<div
class="col-xs-1">'
                . '<span class=" glyphicon glyphicon-sort" style="top: 7px;">'
                . '</span>'
                . '</div>'
                . '<div
class="col-xs-1">'
                . '<span class="input-group-addon" style="padding: 0 5px">'
                // миниатюра картинки
                . '<img id="' . $key . 'Img" src="' . $value . '" style="max-height:32px"
class="form-control">'
                . '</span>'
                . '</div>'
                . '<div class="col-xs-5">'
                . '<input type="text" class="form-control"
name="' . $key
                . '" id="' . $key
                . '" value="' . $value . '">' // замена миниатюры
                . '</div>'
                // картинки
                . '<div class="col-xs-5">'
                . '<input
type="text" class="form-control" name="photo-description'. $key . '" id="photo-description"' . $key . '
value="" placeholder="Описание изображения">'
                . '</div>'
                . '</li>';
        }
        $fieldList .= '</ul>';
        return $fieldList;
    }
}
