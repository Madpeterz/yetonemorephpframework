<?php

namespace YAPF\Framework\Responses\MySQLi;

class UpdateReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly int $itemsUpdated = 0
    ) {
    }
}
