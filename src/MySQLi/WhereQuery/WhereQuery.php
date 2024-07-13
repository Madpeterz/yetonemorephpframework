<?php

namespace YAPF\Framework\MySQLi\WhereQuery;

class WhereQuery
{
    protected array $myWhereFields = [];
    protected array $whereops = [];
    protected string $lastField = "";
    public function field(string $fieldname, string $tableid = ""): WhereSelector
    {
        if ($tableid != "") {
            $fieldname = $tableid . "." . $fieldname;
        }
        $newWhereField = new WhereField($fieldname, $this);
        $this->myWhereFields[] = $newWhereField;
        $this->lastField = $fieldname;
        return $newWhereField->getSelector();
    }

    public function build(): void
    {
    }

    public function and(): self
    {
        if ($this->lastField != "") {
            $this->whereops[$this->lastField] = "AND";
        }
        return $this;
    }

    public function or(): self
    {
        if ($this->lastField != "") {
            $this->whereops[$this->lastField] = "OR";
        }
        return $this;
    }
}
