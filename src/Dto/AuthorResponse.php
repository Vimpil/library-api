<?php

declare(strict_types=1);

namespace App\Dto;

final class AuthorResponse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        /** @var list<int> */
        public readonly array $bookIds = [],
    ) {
    }
}
