<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BookListQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,

        #[Assert\Range(min: 1, max: 100)]
        public readonly int $pageSize = 20,

        public readonly ?string $title = null,
        public readonly string $sort = 'id',
        public readonly string $order = 'asc',
    ) {
    }
}
