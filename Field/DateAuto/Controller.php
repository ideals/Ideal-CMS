<?php
namespace Ideal\Field\DateAuto;

use Ideal\Field\Date;

class Controller extends Date\Controller
{
    protected static $instance;

    public function getValue()
    {
        return time();
    }
}
