<?php

namespace App\SupportedApps\Shelly;

class Shelly extends \App\SupportedApps
{
    public $config;

    public function test()
    {
        $test = parent::appTest($this->normaliseurl($this->config->url, false));

        echo $test->status;
    }
}
