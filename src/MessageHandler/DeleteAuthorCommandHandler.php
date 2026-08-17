<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Author;
use App\Message\DeleteAuthorCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteAuthorCommandHandler
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function __invoke(DeleteAuthorCommand $command): void
    {
        $author = $this->em->find(Author::class, $command->id);

        if ($author === null) {
            throw new NotFoundHttpException(sprintf('Author %d not found.', $command->id));
        }

        $this->em->wrapInTransaction(function () use ($author): void {
            $this->em->remove($author);
            $this->em->flush();
        });
    }
}
