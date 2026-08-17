<?php

declare(strict_types=1);

namespace App\Message;

final class CreateBookCommand
{
    /**
     * @param list<int> $authorIds
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $description = null,
        public readonly array $authorIds = [],
    ) {
    }
}
