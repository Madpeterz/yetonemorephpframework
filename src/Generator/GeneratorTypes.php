<?php

namespace YAPF\Framework\Generator;

use YAPF\Framework\Core\SQLi\SqlConnectedClass as SqlConnectedClass;

abstract class GeneratorTypes extends SqlConnectedClass
{
    public function __construct()
    {
        parent::__construct();
        if (defined('STDIN') == true) {
            $this->console_output = true;
        }
    }
    protected $console_output = false;
    protected $use_output = true;
    protected $countCreated = 0;
    protected $countFailed = 0;
    protected $countRelatedActions = 0;
    public function noOutput(): void
    {
        $this->use_output = false;
    }
    public function getModelsCreated(): int
    {
        return $this->countCreated;
    }
    public function getTotalRelatedActions(): int
    {
        return $this->countRelatedActions;
    }
    public function getModelsFailed(): int
    {
        return $this->countFailed;
    }
}
