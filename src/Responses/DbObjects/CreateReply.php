<?php

namespace YAPF\Framework\Responses\DbObjects;

class CreateReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly ?int $newId = null
    ) {
    }
}
