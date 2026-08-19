<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\AuthorCreateRequest;
use App\Dto\AuthorListQuery;
use App\Dto\AuthorPatchRequest;
use App\Dto\AuthorResponse;
use App\Dto\AuthorUpdateRequest;
use App\Dto\PaginatedResponse;
use App\Dto\PaginationMeta;
use App\Entity\Author;
use App\Entity\Book;
use App\Message\CreateAuthorCommand;
use App\Message\DeleteAuthorCommand;
use App\Message\UpdateAuthorCommand;
use App\Repository\AuthorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/api/authors', name: 'api_authors_')]
final class AuthorController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = ['id', 'name'];
    private const ALLOWED_ORDERS = ['asc', 'desc'];

    public function __construct(
        private readonly AuthorRepository $authorRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: 400)]
        AuthorListQuery $query,
    ): Response {
        $sort = strtolower($query->sort);
        $order = strtolower($query->order);

        if (!in_array($sort, self::ALLOWED_SORT_FIELDS, true)) {
            return $this->json(['error' => sprintf('Invalid sort field "%s". Allowed: %s', $query->sort, implode(', ', self::ALLOWED_SORT_FIELDS))], 400);
        }

        if (!in_array($order, self::ALLOWED_ORDERS, true)) {
            return $this->json(['error' => sprintf('Invalid order "%s". Allowed: %s', $query->order, implode(', ', self::ALLOWED_ORDERS))], 400);
        }

        $result = $this->authorRepository->findPaginated(
            name: $query->name,
            sort: $sort,
            order: $order,
            page: $query->page,
            pageSize: $query->pageSize,
        );

        $items = array_map(fn (Author $a): AuthorResponse => $this->toResponse($a), $result['items']);
        $total = $result['total'];
        $pages = (int) ceil($total / $query->pageSize);

        return $this->json(new PaginatedResponse($items, new PaginationMeta($query->page, $query->pageSize, $total, $pages)));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationFailedStatusCode: 400)]
        AuthorCreateRequest $request,
        MessageBusInterface $bus,
    ): Response {
        try {
            $bus->dispatch(new CreateAuthorCommand(
                name: $request->name,
                bookIds: $request->bookIds,
            ));
        } catch (HandlerFailedException $e) {
            return $this->handleHandlerException($e);
        }

        return new Response(null, 202);
    }

    #[Route('/{id}', name: 'read', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function read(int $id): Response
    {
        $author = $this->em->find(Author::class, $id);

        if ($author === null) {
            return $this->json(['error' => sprintf('Author %d not found.', $id)], 404);
        }

        return $this->json($this->toResponse($author));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        int $id,
        #[MapRequestPayload(
            serializationContext: [AbstractNormalizer::REQUIRE_ALL_PROPERTIES => true],
            validationFailedStatusCode: 400,
        )]
        AuthorUpdateRequest $request,
        MessageBusInterface $bus,
    ): Response {
        try {
            $bus->dispatch(new UpdateAuthorCommand(
                id: $id,
                name: $request->name,
                bookIds: $request->bookIds,
            ));
        } catch (HandlerFailedException $e) {
            return $this->handleHandlerException($e);
        }

        return new Response(null, 202);
    }

    #[Route('/{id}', name: 'patch', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function patch(
        int $id,
        #[MapRequestPayload(validationFailedStatusCode: 400)]
        AuthorPatchRequest $request,
        MessageBusInterface $bus,
    ): Response {
        try {
            $bus->dispatch(new UpdateAuthorCommand(
                id: $id,
                name: $request->name,
                bookIds: $request->bookIds,
            ));
        } catch (HandlerFailedException $e) {
            return $this->handleHandlerException($e);
        }

        return new Response(null, 202);
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(int $id, MessageBusInterface $bus): Response
    {
        try {
            $bus->dispatch(new DeleteAuthorCommand(id: $id));
        } catch (HandlerFailedException $e) {
            return $this->handleHandlerException($e);
        }

        return new Response(null, 202);
    }

    private function toResponse(Author $author): AuthorResponse
    {
        return new AuthorResponse(
            id: $author->getId(),
            name: $author->getName(),
            bookIds: $author->getBooks()->map(fn (Book $b): int => $b->getId())->toArray(),
        );
    }

    private function handleHandlerException(HandlerFailedException $e): Response
    {
        $previous = $e->getPrevious();

        if ($previous instanceof HttpExceptionInterface) {
            return $this->json(['error' => $previous->getMessage()], $previous->getStatusCode());
        }

        return $this->json(['error' => $e->getMessage()], 500);
    }
}
