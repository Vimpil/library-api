<?php

declare(strict_types=1);

namespace App\Dto;

final class BookListQuery
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $pageSize = 20,
        public readonly ?string $title = null,
        public readonly string $sort = 'id',
        public readonly string $order = 'asc',
    ) {
    }
}
