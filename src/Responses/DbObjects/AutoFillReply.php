<?php

namespace YAPF\Framework\Responses\DbObjects;

class AutoFillReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
        public readonly ?array $data = null
    ) {
    }
}
