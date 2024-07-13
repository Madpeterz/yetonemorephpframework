<?php

namespace YAPF\Framework\MySQLi\WhereQuery;

class WhereField
{
    protected WhereSelector $whereSelect = null;
    public function __construct(public string $fieldname, protected WhereQuery $master)
    {
        $this->whereSelect = new WhereSelector($this->master);
    }
    public function getSelector(): WhereSelector
    {
        return $this->whereSelect;
    }
}
