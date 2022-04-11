<?php

namespace YAPF\Framework\Responses\MySQLi;

class CountReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $entrys;
    public function __construct(string $message, bool $status = false, int $entrys = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->entrys = $entrys;
    }
}
