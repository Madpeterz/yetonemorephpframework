<?php

namespace YAPF\Framework\Responses\DbObjects;

class SingleLoadReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false
    ) {
    }
}
