<?php

namespace YAPF\Framework\Responses\DbObjects;

class AgeDetails
{
    public function __construct(
        public readonly bool $cache = false,
        public readonly int $age = 0,
        public readonly int $version = 0
    ) {
    }
}
