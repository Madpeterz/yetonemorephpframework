<?php

namespace YAPF\Framework\Responses\DbObjects;

class MultiUpdateReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly int $changes = 0
    ) {
    }
}
