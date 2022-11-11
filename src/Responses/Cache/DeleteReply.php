<?php

namespace YAPF\Framework\Responses\Cache;

class DeleteReply
{
    public function __construct(
        public readonly string $message,
        public readonly bool $status = false,
    ) {
    }
}
