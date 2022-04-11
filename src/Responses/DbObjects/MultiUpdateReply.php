<?php

namespace YAPF\Framework\Responses\DbObjects;

class MultiUpdateReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $changes;
    public function __construct(string $message, bool $status = false, int $changes = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->changes = $changes;
    }
}
