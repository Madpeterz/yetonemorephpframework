<?php

namespace YAPF\Framework\Responses\Cache;

class ReadReply
{
    public function __construct(
        public readonly string $message,
        public readonly ?string $value = null,
        public readonly bool $status = false,
    ) {
    }
}
