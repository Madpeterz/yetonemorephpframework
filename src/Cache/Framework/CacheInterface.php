<?php

namespace YAPF\Framework\Cache\Framework;

use YAPF\Framework\Responses\Cache\CacheStatusReply;
use YAPF\Framework\Responses\Cache\DeleteReply;
use YAPF\Framework\Responses\Cache\ListKeysReply;
use YAPF\Framework\Responses\Cache\PurgeReply;
use YAPF\Framework\Responses\Cache\ReadReply;
use YAPF\Framework\Responses\Cache\WriteReply;

interface CacheInterface
{
    public function connected(): bool;
    public function readKey(string $key): ReadReply;
    public function writeKey(string $key, string $value, ?int $expireUnixtime = null): WriteReply;
    public function deleteKeys(array $keys): PurgeReply;
    public function deleteKey(string $key): DeleteReply;
    public function listKeys(): ListKeysReply;
    public function hasKey(string $key): CacheStatusReply;
    public function purgeAllKeys(): PurgeReply;
    public function start(): CacheStatusReply;
    public function stop(): void;
    public function driverName(): string;
    public function setTimeout(int $timeout = 2): bool;
    public function getKeyLength(): int;
}
