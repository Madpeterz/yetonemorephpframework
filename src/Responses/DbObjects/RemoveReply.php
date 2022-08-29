<?php

namespace YAPF\Framework\Responses\DbObjects;

class RemoveReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly int $itemsRemoved = 0
    ) {
    }
}
