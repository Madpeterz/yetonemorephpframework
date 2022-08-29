<?php

namespace YAPF\Framework\Responses\MySQLi;

class SelectReply
{
    public readonly int $items;
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly ?array $dataset = null
    ) {
        if ($this->dataset != null) {
            $this->items = count($this->dataset);
            return;
        }
        $this->items = 0;
    }
}
