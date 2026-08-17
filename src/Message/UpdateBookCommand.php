<?php

declare(strict_types=1);

namespace App\Message;

final class UpdateBookCommand
{
    /**
     * @param list<int>|null $authorIds null means unchanged
     */
    public function __construct(
        public readonly int $id,
        public readonly bool $fullReplacement,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?array $authorIds = null,
    ) {
    }
}
