<?php

namespace YAPF\Framework\Responses\Cache;

class StatsReply
{
    public function __construct(
        public readonly int $reads,
        public readonly int $writes,
        public readonly int $deletes
    ) {
    }
}
