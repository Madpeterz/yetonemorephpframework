<?php

namespace YAPF\Framework\Responses\MySQLi;

class AddReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly ?int $newId = null
    ) {
    }
}
