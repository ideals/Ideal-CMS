<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\YandexWebmasterText;

use Ideal\Field\AbstractController;

/**
 * Поле отправки текста в сервис "Яндекс.Вебмастер"
 *
 * Поле представляет из себя область для ввода текста и кнопку для отправки даных в сервис "Яндекс.Вебмастер.
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'yandexwebmastertext' => array(
 *         'label' => 'Текст для отправки в Яндекс.Вебмастер',
 *         'sql'   => 'mediumtext',
 *         'type'  => 'Ideal_YandexWebmasterText'
 *     ),
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $value = htmlspecialchars($this->getValue());
        $html = <<<HTML
            <div id="{$this->htmlName}-control-group">{$this->getLabelText()}<br />
            <textarea name="{$this->htmlName}" id="yw{$this->htmlName}" class="form-control">{$value}</textarea>
            <div class="text-center" style="margin-top: 10px;">
                    <span class="input-group-btn">
                        <button class="btn" onclick="sendYWT('yw{$this->htmlName}'); return false;">
                            Отправить текст в Яндекс.Вебмастер
                        </button>
                    </span>
                </div>
            </div>            
HTML;
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        return '';
    }

    public function getValue()
    {
        $value = parent::getValue();
        if (!$value) {
            // Получаем данные из полей определённых в параметре "content_fields"
            foreach ($this->model->params['content_fields'] as $field) {
                if (strpos($field, 'addon') === 0) {
                    $value .= $this->model->getNeighborAddonsData($field);
                } else {
                    $value .= $this->model->getFieldData($field);
                }
            }
        }
        // Ставим перед открывающимися тегами переноса
        $value = str_replace('<p', ' <p', $value);
        $value = str_replace('<br', ' <br', $value);
        $value = strip_tags($value);
        return $value;
    }
}
