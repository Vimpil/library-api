<?php

declare(strict_types=1);

namespace App\Dto;

final class BookResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        /** @var list<int> */
        public readonly array $authorIds = [],
    ) {
    }
}
