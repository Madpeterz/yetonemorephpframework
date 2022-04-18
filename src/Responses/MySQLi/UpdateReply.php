<?php

namespace YAPF\Framework\Responses\MySQLi;

class UpdateReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $itemsUpdated;
    public function __construct(string $message, bool $status = false, int $itemsUpdated = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->itemsUpdated = $itemsUpdated;
    }
}
