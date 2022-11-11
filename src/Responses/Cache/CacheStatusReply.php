<?php

namespace YAPF\Framework\Responses\Cache;

class CacheStatusReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
    ) {
    }
}
