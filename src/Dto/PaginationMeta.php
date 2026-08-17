<?php

declare(strict_types=1);

namespace App\Dto;

final class PaginationMeta
{
    public function __construct(
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $total,
        public readonly int $pages,
    ) {
    }
}
