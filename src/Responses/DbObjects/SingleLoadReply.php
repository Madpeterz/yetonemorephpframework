<?php

namespace YAPF\Framework\Responses\DbObjects;

class SingleLoadReply
{
    public readonly bool $status;
    public readonly string $message;
    public function __construct(string $message, bool $status = false)
    {
        $this->status = $status;
        $this->message = $message;
    }
}
