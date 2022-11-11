<?php

namespace YAPF\Framework\Responses\Cache;

class ListKeysReply
{
    public function __construct(
        public readonly string $message,
        public readonly array $keys = [],
        public readonly bool $status = false,
    ) {
    }
}
