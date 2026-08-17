<?php

declare(strict_types=1);

namespace App\Message;

final class CreateAuthorCommand
{
    /**
     * @param list<int> $bookIds
     */
    public function __construct(
        public readonly string $name,
        public readonly array $bookIds = [],
    ) {
    }
}
