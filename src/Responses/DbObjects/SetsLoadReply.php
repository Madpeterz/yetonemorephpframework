<?php

namespace YAPF\Framework\Responses\DbObjects;

class SetsLoadReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly int $items = 0
    ) {
    }
}
