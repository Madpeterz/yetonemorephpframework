<?php

namespace YAPF\Framework\Responses\DbObjects;

class SetsLoadReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $items;
    public function __construct(string $message, bool $status = false, int $items = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->items = $items;
    }
}
