<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Author;
use App\Entity\Book;
use App\Message\CreateAuthorCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateAuthorCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(CreateAuthorCommand $command): void
    {
        $this->em->wrapInTransaction(function () use ($command): void {
            $author = new Author();
            $author->setName($command->name);

            if ($command->bookIds !== []) {
                $books = $this->em->getRepository(Book::class)->findBy(['id' => $command->bookIds]);
                $foundIds = array_map(fn (Book $b): int => $b->getId(), $books);
                $missingIds = array_diff($command->bookIds, $foundIds);

                if ($missingIds !== []) {
                    throw new BadRequestHttpException(
                        sprintf('Book IDs not found: %s', implode(', ', $missingIds)),
                    );
                }

                foreach ($books as $book) {
                    $author->addBook($book);
                }
            }

            $this->em->persist($author);
            $this->em->flush();
        });
    }
}
