<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Author;
use App\Entity\Book;
use App\Message\UpdateBookCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateBookCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(UpdateBookCommand $command): void
    {
        $book = $this->em->find(Book::class, $command->id);

        if ($book === null) {
            throw new NotFoundHttpException(sprintf('Book %d not found.', $command->id));
        }

        $this->em->wrapInTransaction(function () use ($book, $command): void {
            if ($command->title !== null) {
                $book->setTitle($command->title);
            }

            if ($command->fullReplacement || $command->description !== null) {
                $book->setDescription($command->description);
            }

            if ($command->authorIds !== null) {
                $authors = [];
                if ($command->authorIds !== []) {
                    $authors = $this->em->getRepository(Author::class)->findBy(['id' => $command->authorIds]);
                    $foundIds = array_map(fn (Author $a): int => $a->getId(), $authors);
                    $missingIds = array_diff($command->authorIds, $foundIds);

                    if ($missingIds !== []) {
                        throw new BadRequestHttpException(
                            sprintf('Author IDs not found: %s', implode(', ', $missingIds)),
                        );
                    }
                }
                $book->replaceAuthors($authors);
            }

            $this->em->flush();
        });
    }
}
