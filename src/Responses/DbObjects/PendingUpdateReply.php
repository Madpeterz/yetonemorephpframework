<?php

namespace YAPF\Framework\Responses\DbObjects;

class PendingUpdateReply
{
    public readonly int $changes;
    public function __construct(
        public readonly string $errorMsg = "",
        public readonly bool $vaild = true,
        public readonly array $fieldsChanged = [],
        public readonly array $oldValues = [],
        public readonly array $newValues = [],
    ) {
        $this->changes = count($fieldsChanged);
    }
}
