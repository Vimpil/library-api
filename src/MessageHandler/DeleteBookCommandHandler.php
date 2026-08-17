<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Book;
use App\Message\DeleteBookCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteBookCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(DeleteBookCommand $command): void
    {
        $book = $this->em->find(Book::class, $command->id);

        if ($book === null) {
            throw new NotFoundHttpException(sprintf('Book %d not found.', $command->id));
        }

        $this->em->wrapInTransaction(function () use ($book): void {
            $this->em->remove($book);
            $this->em->flush();
        });
    }
}
