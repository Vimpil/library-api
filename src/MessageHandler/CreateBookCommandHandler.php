<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Author;
use App\Entity\Book;
use App\Message\CreateBookCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateBookCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(CreateBookCommand $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $book = new Book();
            $book->setTitle($command->title);
            $book->setDescription($command->description);

            if ($command->authorIds !== []) {
                $authors = $this->em->getRepository(Author::class)->findBy(['id' => $command->authorIds]);
                $foundIds = array_map(fn (Author $a): int => $a->getId(), $authors);
                $missingIds = array_diff($command->authorIds, $foundIds);

                if ($missingIds !== []) {
                    throw new BadRequestHttpException(
                        sprintf('Author IDs not found: %s', implode(', ', $missingIds)),
                    );
                }

                foreach ($authors as $author) {
                    $book->addAuthor($author);
                }
            }

            $this->em->persist($book);
            $this->em->flush();
        });
    }
}
