<?php

namespace YAPF\Framework\Responses\MySQLi;

class SelectReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $items;
    public readonly ?array $dataset;
    public function __construct(string $message, bool $status = false, ?array $dataset = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->dataset = $dataset;
        if ($this->dataset != null) {
            $this->items = count($this->dataset);
            return;
        }
        $this->items = 0;
    }
}
