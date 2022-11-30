<?php

namespace YAPF\Framework\Database\QueryBuilder;

use YAPF\Core\ErrorControl\ErrorLogging;
use YAPF\Framework\Database\QueryBuilder\Enums\OrderByDirection;

class SelectBuilder extends ErrorLogging
{
    protected ?string $targetTable = null;
    protected ?array $targetFields = null;
    protected ?WhereBuilder $whereConfig = null;
    protected bool $distinct = false;
    protected int $limit = 0;
    protected int $page = 0;
    protected ?string $orderBy = null;
    protected ?OrderByDirection $OrderByDirection = null;
    protected ?string $groupBy = null;

    public function fields(?array $fieldnames = null): self
    {
        $this->targetFields = $fieldnames;
        return $this;
    }

    public function page(int $pageNumber = 0): self
    {
        if ($pageNumber < 0) {
            $pageNumber = 0;
        }
        $this->page = $pageNumber;
        return $this;
    }

    public function limit($limtAmount = 0): self
    {
        if ($limtAmount < 0) {
            $limtAmount = 0;
        }
        $this->limit = $limtAmount;
        return $this;
    }

    public function distinct(bool $distint = true): self
    {
        $this->distinct = $distint;
        return $this;
    }

    public function from(string $tablename): self
    {
        $this->targetTable = $tablename;
        return $this;
    }

    public function where(?WhereBuilder &$whereBuilder = null): self
    {
        $this->whereConfig = $whereBuilder;
        return $this;
    }

    public function groupBy(?string $groupByField = null): self
    {
        $this->groupBy = $groupByField;
        return $this;
    }

    public function orderBy(?string $field, OrderByDirection $dir = OrderByDirection::ASCENDING): self
    {
        $this->orderBy = $field;
        $this->OrderByDirection = $dir;
        return $this;
    }

    public function asJson(): string
    {
        $orderDir = "";
        if ($this->OrderByDirection != null) {
            $orderDir = $this->OrderByDirection->value;
        }
        return json_encode([
            "table" => $this->targetTable,
            "fields" => $this->targetFields,
            "distinct" => $this->distinct,
            "where" => $this->whereConfig->asJson(),
            "limit" => $this->limit,
            "page" => $this->page,
            "group" => $this->groupBy,
            "order" => $this->orderBy,
            "orderDir" => $orderDir,
        ]);
    }

    public function asSql(): ?string
    {
        if ($this->targetTable == null) {
            $this->addError("No table selected");
            return null;
        }
        $sql = "SELECT";
        if ($this->distinct == true) {
            $sql .= " DISTINCT";
        }
        $fields = "*";
        if ($this->targetFields != null) {
            if (count($this->targetFields) > 0) {
                $fields = implode(",", $this->targetFields);
            }
        }
        $sql .= " " . $fields;
        $sql .= " FROM " . $this->targetTable;
        if ($this->whereConfig != null) {
            $sql .= " " . $this->whereConfig->asSql();
        }
        if ($this->groupBy != null) {
            $sql .= " GROUP BY " . $this->groupBy;
        }
        if (($this->orderBy != null) && ($this->OrderByDirection != null)) {
            $sql .= " ORDER BY " . $this->orderBy . " " . $this->OrderByDirection->value;
        }
        if ($this->limit != 0) {
            $offset = $this->limit * $this->page;
            $sql .= " LIMIT " . $offset . "," . $this->limit;
        }
        return $sql;
    }


    /*
        reset the builder
        - used if you want todo multiple querys but
        dont want to create a new object each time
    */
    public function reset(): self
    {
        $this->targetTable = null;
        $this->targetFields = null;
        $this->whereConfig = null;
        $this->distinct = false;
        $this->limit = 0;
        $this->page = 0;
        $this->orderBy = null;
        $this->OrderByDirection = null;
        $this->groupBy = null;
        return $this;
    }
}
