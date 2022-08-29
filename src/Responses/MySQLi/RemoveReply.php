<?php

namespace YAPF\Framework\Responses\MySQLi;

class RemoveReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly int $itemsRemoved = 0
    ) {
    }
}
