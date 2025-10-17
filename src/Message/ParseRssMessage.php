<?php

declare(strict_types=1);

namespace App\Message;

readonly class ParseRssMessage
{
    public function __construct(
        private int $sourceId,
    ) {}

    public function getSourceId(): int
    {
        return $this->sourceId;
    }
}
