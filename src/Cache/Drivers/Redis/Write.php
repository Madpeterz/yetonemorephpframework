<?php

namespace YAPF\Framework\Cache\Drivers\Redis;

use Throwable;
use YAPF\Framework\Responses\Cache\DeleteReply;
use YAPF\Framework\Responses\Cache\PurgeReply;
use YAPF\Framework\Responses\Cache\WriteReply;

abstract class Write extends Read
{
    public function deleteKeys(array $keys): PurgeReply
    {
        if ($this->readyToTakeAction() == false) {
            return new PurgeReply($this->getLastErrorBasic());
        }
        if (count($keys) < 0) {
            return new PurgeReply("no keys given", 0, true);
        }
        $deleteOk = true;
        $deleteMessage = "All keys deleted";
        $deleteCount = 0;
        foreach ($keys as $key) {
            $reply = $this->deleteKey($key);
            if ($reply->status == false) {
                $deleteOk = false;
                $deleteMessage = $reply->message;
                break;
            }
            $deleteCount++;
        }
        return new PurgeReply($deleteMessage, $deleteCount, $deleteOk);
    }

    public function purgeAllKeys(): PurgeReply
    {
        if ($this->readyToTakeAction() == false) {
            return new PurgeReply($this->getLastErrorBasic());
        }
        $reply = $this->listKeys();
        if ($reply->status == false) {
            return new PurgeReply($reply->message);
        }
        return $this->deleteKeys($reply->keys);
    }

    public function writeKey(string $key, string $value, ?int $expireUnixtime = null): WriteReply
    {
        if ($this->readyToTakeAction() == false) {
            return new WriteReply($this->getLastErrorBasic());
        }
        if ($expireUnixtime < time()) {
            return new WriteReply("Invaild expire unixtime");
        }
        if (strlen($key) > 1000) {
            return new WriteReply("Invaild key length [max 1000]");
        }
        try {
            $this->client->setex($key, $expireUnixtime - time(), $value);
            $this->keyWrites++;
            return new WriteReply("ok", true);
        } catch (Throwable $ex) {
            $this->addError("failed to write key: " .
            $ex->getMessage() . " Key: " . $key);
            $this->disconnected = true;
        }
        return new WriteReply($this->getLastErrorBasic());
    }

    public function deleteKey(string $key): DeleteReply
    {
        if ($this->readyToTakeAction() == false) {
            return new DeleteReply($this->getLastErrorBasic());
        }
        if (strlen($key) > 1000) {
            return new DeleteReply("Invaild key length [max 1000]");
        }
        if ($this->hasKey($key) == false) {
            $this->keyDeletes++;
            return new DeleteReply("not found but thats fine", true);
        }
        try {
            if ($this->client->del($key) == 1) {
                $this->keyDeletes++;
                return new DeleteReply("ok", true);
            }
            $this->addError("Failed to remove " . $key . " from server");
        } catch (Throwable $ex) {
            $this->disconnected = true;
            $this->addError("failed to delete key: " . $ex->getMessage());
        }
        return new DeleteReply($this->getLastErrorBasic());
    }
}
