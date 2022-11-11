<?php

namespace YAPF\Framework\Responses\Cache;

class PurgeReply
{
    public function __construct(
        public readonly string $message,
        public readonly int $keysDeleted = 0,
        public readonly bool $status = false,
    ) {
    }
}
