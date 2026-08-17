<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class AuthorUpdateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $name,

        /** @var list<int> */
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\GreaterThan(0),
        ])]
        public readonly array $bookIds,
    ) {
    }
}
