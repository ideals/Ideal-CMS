<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Template;

use Ideal\Field\Select;
use Ideal\Core\Request;

/**
 * Специальное поле, предоставляющее возможность выбрать шаблон для отображения структуры
 *
 *
 * Пример объявления в конфигурационном файле структуры:
 *
 *     'template' => array(
 *         'label' => 'Шаблон отображения',
 *         'sql' => "varchar(255) default 'index.twig'",
 *         'type' => 'Ideal_Template',
 *         'medium' => '\\Ideal\\Medium\\TemplateList\\Model',
 *         'default'   => 'index.twig',
 *
 * В поле medium указывается класс, отвечающий за предоставление списка элементов для select.
 */
class Controller extends Select\Controller
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {

        $html = '';

        // Подключаем скрипт смены списка шабонов, только если доступна более чем одна структура
        if (count($this->list) > 1) {
            $html .= '<script type="text/javascript" src="Ideal/Field/Template/templateShowing.js" />';
        }

        // Получаем значение поумолчанию для структуры
        $pageData = $this->model->getPageData();
        if (isset($pageData['structure']) && !empty($pageData['structure'])) {
            $structureValue = $pageData['structure'];
        } else {
            reset($this->list);
            $structureValue = key($this->list);
        }

        // Составляем списки шаблонов
        foreach ($this->list as $key => $value) {
            // индикатор показа списка по умолчанию
            $structureValue == $key ? $display = "block" : $display = "none";
            $key = strtolower($key);
            $nameId = $this->htmlName . '_' . $key;

            // Построение тега "select" со списком доступных шаблонов
            $html .= '<select class="form-control" id="' . $nameId . '" >';
            $defaultValue = $this->getValue();
            foreach ($value as $k => $v) {
                $selected = '';
                if ($k == $defaultValue) {
                    $selected = ' selected="selected"';
                }
                $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $html .= '</select>';

            // js скрипт инициализирующий модификацию тега "select" для возможности вставки собственного значения
            $html .= <<<HTML
            <script>
                (function( $ ) {
                    $.widget( "custom.combobox", {
                        _create: function() {
                                this.element.hide();
                                this._createAutocomplete();
                                this._createShowAllButton();
                        },

                        _createAutocomplete: function() {
                            var selected = this.element.children( ":selected" );
                            value = selected.val() ? selected.text() : "";

                            this.input = $( "<input>" )
                                .insertAfter( this.element )
                                .val( value )
                                .attr( "title", "" )
                                .attr( "name", "{$nameId}" )
                                .addClass( "custom-combobox-input ui-widget ui-widget-content ui-state-default "
                                    + "ui-corner-left general_template_{$key} form-control")
                                .css("display", "{$display}")

                                .autocomplete({
                                    delay: 0,
                                    minLength: 0,
                                    appendTo: ".general_template-controls",
                                    source: $.proxy( this, "_source" )
                                })
                                .tooltip({
                                    tooltipClass: "ui-state-highlight"
                                });

                                this._on( this.input, {
                                    autocompleteselect: function( event, ui ) {
                                        ui.item.option.selected = true;
                                        this._trigger( "select", event, {
                                            item: ui.item.option
                                        });
                                    },
                                });
                        },

                        _createShowAllButton: function() {
                            var input = this.input,
                            wasOpen = false;

                            $( "<a>" )
                                .attr( "tabIndex", -1 )
                                .tooltip()
                                .insertAfter( this.element )
                                .button({
                                    icons: {
                                        primary: "ui-icon-triangle-1-s"
                                    },
                                    text: false
                                })
                                .removeClass( "ui-corner-all" )
                                .addClass( "custom-combobox-toggle ui-corner-right general_template_{$key}" )
                                .css("display", "{$display}")
                                .html("<span class=\"arrow-down\"></span>")
                                .mousedown(function() {
                                    wasOpen = input.autocomplete( "widget" ).is(":visible");
                                })
                                .click(function() {
                                    input.focus();

                                    if ( wasOpen ) {
                                        return;
                                    }

                                    input.autocomplete( "search", "" );
                                });
                        },

                        _source: function( request, response ) {
                            var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
                            response( this.element.children( "option" ).map(function() {
                                var text = $( this ).text();
                                if ( this.value && ( !request.term || matcher.test(text) ) )
                                return {
                                    label: text,
                                    value: text,
                                    option: this
                                };
                             }) );
                        },
                    });
                })( jQuery );
                $(function() {
                    $("#{$nameId}").combobox();
                    $("#{$nameId}").siblings('input.{$nameId}').click(function(){
                        if ($(this).autocomplete( "widget" ).is(":visible")) {
                            $(this).autocomplete( "close" );
                        } else {
                            $(this).autocomplete( "search", "" );
                        }
                    });
                });
            </script>
HTML;

        }
        return $html;
    }

    /**
     * Получение нового значения поля шаблона из данных, введённых пользователем, с учётом "Типа раздела"
     *
     * @return string
     */
    public function pickupNewValue()
    {
        $request = new Request();

        // Вычисляем последний префикс с учётом того что поля выбора типа структуры может не существовать
        if (isset($request->general_structure)) {
            $lastPrefix = strtolower($request->general_structure);
        } else {
            $objClassName = get_class($this->model);
            $objClassNameSlice = explode('\\', $objClassName);
            $lastPrefix = strtolower($objClassNameSlice[0] . '_' . $objClassNameSlice[2]);
        }

        $fieldName = $this->groupName . '_' . $this->name . '_' . $lastPrefix;

        // Получаем название файла, так как полный путь используется только для удобства представления
        $fileName = basename($request->$fieldName);
        $this->newValue = $fileName;
        return $this->newValue;
    }
}
