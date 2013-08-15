<?php
namespace Ideal\Field\RichEdit;

use Ideal\Field\AbstractController;
use Ideal\Core\Config;

class Controller extends AbstractController
{
    protected static $instance;


    public function getInputText()
    {
        $config = Config::getInstance();
        $html = <<<HTML
            <textarea class="{$this->widthEditField}" name="{$this->htmlName}"
                id="{$this->htmlName}">{$this->getValue()}</textarea>
            <script>
                CKFinder.setupCKEditor( null, "/{$config->cmsFolder}/Ideal/Library/ckfinder/" );
                CKEDITOR.replace("{$this->htmlName}", {
                toolbar: [
                    [ "Source", "-", "Preview", "Print", "-", "Templates" ],
                    [ "PasteText", "PasteFromWord", "-", "Undo", "Redo" ],
                    [ "Find", "Replace", "-", "Scayt" ],
                    [ "Image", "Flash", "MediaEmbed", "Table", "HorizontalRule", "SpecialChar" ],
                    "/",
                    [ "Bold", "Italic", "Underline", "Strike", "Subscript", "Superscript", "-", "RemoveFormat" ],
                    [ "NumberedList", "BulletedList", "-", "Outdent", "Indent", "-", "Blockquote", "CreateDiv", "-", "JustifyLeft", "JustifyCenter", "JustifyRight", "JustifyBlock" ],
                    [ "Link", "Unlink", "Anchor" ],
                    "/",
                    [ "Styles", "Format", "Font", "FontSize" ],
                    [ "TextColor", "BGColor" ],
                    [ "Maximize", "ShowBlocks" ],
                    [ "About" ]
                ]});
            </script>
HTML;
        // Из стандартного комплекта кнопок были исключены следующие:
        // 'Save', 'NewPage', 'DocProps', 'Cut', 'Copy', 'Paste', 'SelectAll', 'Form', 'Checkbox',
        // 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField',
        // 'Smiley',  'PageBreak', 'Iframe'

        return $html;
    }


    public function showEdit()
    {
        $html = '<div id="' . $this->htmlName . '-control-group" class="control-group">'
            . $this->getLabelText() . '<br />' . $this->getInputText() . '</div>';
        return $html;
    }

}