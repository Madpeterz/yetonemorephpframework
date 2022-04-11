<?php

namespace YAPF\Framework\Responses\MySQLi;

class UpdateReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $entrysUpdated;
    public function __construct(string $message, bool $status = false, int $entrysUpdated = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->entrysUpdated = $entrysUpdated;
    }
}
