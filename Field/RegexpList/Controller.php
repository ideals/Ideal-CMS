<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\RegexpList;

use Ideal\Field\AbstractController;

/**
 * Отображение редактирования поля в админке в виде textarea
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'exceptions' => array(
 *         'label' => 'Исключения',
 *         'sql'   => 'text',
 *         'type'  => 'Ideal_RegexpList'
 *     ),
 */
class Controller extends AbstractController
{
    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText(): string
    {
        return
            '<textarea class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '">' . htmlspecialchars($this->getValue()) . '</textarea>';
    }

    /**
     * {@inheritdoc}
     */
    public function parseInputValue($isCreate)
    {
        $item = parent::parseInputValue($isCreate);

        // Экранируем переводы строки для обработки каждой строки
        $string = str_replace("\r", '', $this->newValue);
        $lines = array_filter(explode("\n", $string));

        foreach ($lines as $line) {
            // Проверка на соответствие формату регулярного выражения, если нет, то уведомляем об этом
            if (!preg_match("/^\/.*\/[imsxADSUXJu]{0,11}$/", $line)) {
                $item['message'] = sprintf('Строка %s не удовлятворяет формату регулярных выражений.', $line);
            }
        }

        return $item;
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue(): string
    {
        // В исключениях не нужны пустые строки
        $string = str_replace("\r", '', parent::pickupNewValue());
        return implode("\n", array_filter(explode("\n", $string)));
    }
}
