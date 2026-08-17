<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Author;
use App\Entity\Book;
use App\Message\UpdateAuthorCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateAuthorCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(UpdateAuthorCommand $command): void
    {
        $author = $this->em->find(Author::class, $command->id);

        if ($author === null) {
            throw new NotFoundHttpException(sprintf('Author %d not found.', $command->id));
        }

        $this->em->wrapInTransaction(function () use ($author, $command): void {
            if ($command->name !== null) {
                $author->setName($command->name);
            }

            if ($command->bookIds !== null) {
                $books = [];
                if ($command->bookIds !== []) {
                    $books = $this->em->getRepository(Book::class)->findBy(['id' => $command->bookIds]);
                    $foundIds = array_map(fn (Book $b): int => $b->getId(), $books);
                    $missingIds = array_diff($command->bookIds, $foundIds);

                    if ($missingIds !== []) {
                        throw new BadRequestHttpException(
                            sprintf('Book IDs not found: %s', implode(', ', $missingIds)),
                        );
                    }
                }
                $author->replaceBooks($books);
            }

            $this->em->flush();
        });
    }
}
