<?php

namespace YAPF\Framework\Responses\DbObjects;

class CreateUidReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly string $uid = ""
    ) {
    }
}
