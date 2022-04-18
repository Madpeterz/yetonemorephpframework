<?php

namespace YAPF\Framework\Responses\DbObjects;

class CreateReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly ?int $newId;
    public function __construct(string $message, bool $status = false, ?int $newId = null)
    {
        $this->status = $status;
        $this->message = $message;
        $this->newId = $newId;
    }
}
