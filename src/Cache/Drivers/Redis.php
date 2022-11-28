<?php

namespace YAPF\Framework\Cache\Drivers;

use YAPF\Framework\Cache\Drivers\Framework\CacheInterface;
use YAPF\Framework\Cache\Drivers\Redis\Write as RedisWrite;

class Redis extends RedisWrite implements CacheInterface
{
}
