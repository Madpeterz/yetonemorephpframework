<?php

namespace YAPF\Framework\Generator;

class GeneratorDefaults extends GeneratorTypes
{
    protected $tabLookup = [];
    protected $fileLines = [];
    protected $output = "";

    public function getOutput(): string
    {
        return $this->output;
    }

    public function __construct()
    {
        $this->output = "";
        $this->UseTabs(true);
        parent::__construct();
    }

    public function useTabs($iAmAMonster = false): void
    {
        $this->tabLookup = [0 => "",1 => "    ",2 => "        ",3 => "            "];
        if ($iAmAMonster == false) {
            $this->tabLookup = [0 => "",1 => "\t",2 => "\t\t",3 => "\t\t\t"];
        }
    }
}
