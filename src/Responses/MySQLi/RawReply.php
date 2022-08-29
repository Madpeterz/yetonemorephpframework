<?php

namespace YAPF\Framework\Responses\MySQLi;

class RawReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly int $commandsRun = 0
    ) {
    }
}
