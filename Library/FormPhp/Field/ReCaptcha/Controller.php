<?php

namespace FormPhp\Field\ReCaptcha;

use FormPhp\Field\AbstractField;

/**
 * Поле капчи от google
 *
 */
class Controller extends AbstractField
{
    public function getInputText()
    {
        return <<<RECAPCHA
<script src='https://www.google.com/recaptcha/api.js'></script>
<div class="g-recaptcha" data-sitekey="{$this->options['siteKey']}"></div>
RECAPCHA;
    }
}
