<?php

declare(strict_types=1);

namespace App\Dto;

final class PaginatedResponse
{
    /**
     * @param list<object> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly PaginationMeta $pagination,
    ) {
    }
}
