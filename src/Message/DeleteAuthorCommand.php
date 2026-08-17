<?php

declare(strict_types=1);

namespace App\Message;

final class DeleteAuthorCommand
{
    public function __construct(
        public readonly int $id,
    ) {
    }
}
