<?php

namespace YAPF\Framework\Responses\MySQLi;

class RawReply
{
    public readonly bool $status;
    public readonly string $message;
    public readonly int $commandsRun;
    public function __construct(string $message, bool $status = false, int $commandsRun = 0)
    {
        $this->status = $status;
        $this->message = $message;
        $this->commandsRun = $commandsRun;
    }
}
