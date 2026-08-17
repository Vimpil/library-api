<?php

declare(strict_types=1);

namespace App\Message;

final class UpdateAuthorCommand
{
    /**
     * @param list<int>|null $bookIds null means unchanged
     */
    public function __construct(
        public readonly int $id,
        public readonly ?string $name = null,
        public readonly ?array $bookIds = null,
    ) {
    }
}
