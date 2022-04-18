<?php

namespace YAPF\Framework\Responses\MySQLi;

class RemoveReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $entrysRemoved;
    public function __construct(string $message, bool $status = false, int $entrysRemoved = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->entrysRemoved = $entrysRemoved;
    }
}
