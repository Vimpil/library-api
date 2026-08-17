<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BookCreateRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 255)]
        public readonly string $title = '',

        public readonly ?string $description = null,

        /** @var list<int> */
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\GreaterThan(0),
        ])]
        public readonly array $authorIds = [],
    ) {
    }
}
