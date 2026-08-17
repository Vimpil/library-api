<?php

declare(strict_types=1);

namespace App\Message;

final class DeleteBookCommand
{
    public function __construct(
        public readonly int $id,
    ) {
    }
}
