<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class BookPatchRequest
{
    public function __construct(
        #[Assert\NotBlank(allowNull: true, normalizer: 'trim')]
        #[Assert\Length(max: 255)]
        public readonly ?string $title = null,

        public readonly ?string $description = null,

        /** @var list<int>|null */
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\GreaterThan(0),
        ])]
        public readonly ?array $authorIds = null,
    ) {
    }
}
