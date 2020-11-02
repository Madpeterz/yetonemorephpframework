<?php

namespace YAPF\InputFilter\Worker;

use YAPF\ErrorLogging as ErrorLogging;

abstract class InputFilterWorkerBase extends ErrorLogging
{
    protected $failure = false;
    protected $testOK = true;
    protected $whyfailed = "";

    public function get_why_failed()
    {
        return $this->whyfailed;
    }
}
