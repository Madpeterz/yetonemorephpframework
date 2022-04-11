<?php

namespace YAPF\Framework\Responses\MySQLi;

class AddReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly ?int $newid;
    public function __construct(string $message, bool $status = false, ?int $newid = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->newid = $newid;
    }
}
