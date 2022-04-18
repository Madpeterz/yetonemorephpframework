<?php

namespace YAPF\Framework\Responses\DbObjects;

class CreateUidReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly string $uid;
    public function __construct(string $message, bool $status = false, string $uid = "")
    {
        $this->status = $status;
        $this->message = $message;
        $this->uid = $uid;
    }
}
