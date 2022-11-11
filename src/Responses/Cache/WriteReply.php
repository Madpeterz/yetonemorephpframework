<?php

namespace YAPF\Framework\Responses\Cache;

class WriteReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
    ) {
    }
}
