<?php

use YAPF\ErrorLogging as ErrorLogging;

abstract class inputFilter_base extends ErrorLogging
{
    protected $failure = false;
    protected $testOK = true;
    protected $whyfailed = "";

    public function get_why_failed()
    {
        return $this->whyfailed;
    }
}
