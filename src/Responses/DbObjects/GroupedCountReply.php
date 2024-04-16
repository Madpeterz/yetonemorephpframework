<?php

namespace YAPF\Framework\Responses\DbObjects;

class GroupedCountReply
{
    public function __construct(
        public readonly string $message,
        public readonly ?array $results = null,
        public readonly bool $status = false,
    ) {
    }
}
