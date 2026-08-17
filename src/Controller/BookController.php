<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\BookCreateRequest;
use App\Dto\BookListQuery;
use App\Dto\BookPatchRequest;
use App\Dto\BookResponse;
use App\Dto\BookUpdateRequest;
use App\Dto\PaginatedResponse;
use App\Dto\PaginationMeta;
use App\Entity\Author;
use App\Entity\Book;
use App\Message\CreateBookCommand;
use App\Message\DeleteBookCommand;
use App\Message\UpdateBookCommand;
use App\Repository\BookRepository;
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

#[Route('/api/books', name: 'api_books_')]
final class BookController extends AbstractController
{
    private const ALLOWED_SORT_FIELDS = ['id', 'title'];
    private const ALLOWED_ORDERS = ['asc', 'desc'];

    public function __construct(
        private readonly BookRepository $bookRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(
        #[MapQueryString(validationFailedStatusCode: 400)]
        BookListQuery $query,
    ): Response {
        $sort = strtolower($query->sort);
        $order = strtolower($query->order);

        if (!in_array($sort, self::ALLOWED_SORT_FIELDS, true)) {
            return $this->json(['error' => sprintf('Invalid sort field "%s". Allowed: %s', $query->sort, implode(', ', self::ALLOWED_SORT_FIELDS))], 400);
        }

        if (!in_array($order, self::ALLOWED_ORDERS, true)) {
            return $this->json(['error' => sprintf('Invalid order "%s". Allowed: %s', $query->order, implode(', ', self::ALLOWED_ORDERS))], 400);
        }

        $pageSize = min(max($query->pageSize, 1), 100);
        $page = max($query->page, 1);

        $result = $this->bookRepository->findPaginated(
            title: $query->title,
            sort: $sort,
            order: $order,
            page: $page,
            pageSize: $pageSize,
        );

        $items = array_map(fn (Book $b): BookResponse => $this->toResponse($b), $result['items']);
        $total = $result['total'];
        $pages = $pageSize > 0 ? (int) ceil($total / $pageSize) : 0;

        return $this->json(new PaginatedResponse($items, new PaginationMeta($page, $pageSize, $total, $pages)));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(
        #[MapRequestPayload(validationFailedStatusCode: 400)]
        BookCreateRequest $request,
        MessageBusInterface $bus,
    ): Response {
        try {
            $bus->dispatch(new CreateBookCommand(
                title: $request->title,
                description: $request->description,
                authorIds: $request->authorIds,
            ));
        } catch (HandlerFailedException $e) {
            return $this->handleHandlerException($e);
        }

        return new Response(null, 202);
    }

    #[Route('/{id}', name: 'read', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function read(int $id): Response
    {
        $book = $this->em->find(Book::class, $id);

        if ($book === null) {
            return $this->json(['error' => sprintf('Book %d not found.', $id)], 404);
        }

        return $this->json($this->toResponse($book));
    }

    #[Route('/{id}', name: 'update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(
        int $id,
        #[MapRequestPayload(
            serializationContext: [AbstractNormalizer::REQUIRE_ALL_PROPERTIES => true],
            validationFailedStatusCode: 400,
        )]
        BookUpdateRequest $request,
        MessageBusInterface $bus,
    ): Response {
        try {
            $bus->dispatch(new UpdateBookCommand(
                id: $id,
                fullReplacement: true,
                title: $request->title,
                description: $request->description,
                authorIds: $request->authorIds,
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
        BookPatchRequest $request,
        MessageBusInterface $bus,
    ): Response {
        try {
            $bus->dispatch(new UpdateBookCommand(
                id: $id,
                fullReplacement: false,
                title: $request->title,
                description: $request->description,
                authorIds: $request->authorIds,
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
            $bus->dispatch(new DeleteBookCommand(id: $id));
        } catch (HandlerFailedException $e) {
            return $this->handleHandlerException($e);
        }

        return new Response(null, 202);
    }

    private function toResponse(Book $book): BookResponse
    {
        return new BookResponse(
            id: $book->getId(),
            title: $book->getTitle(),
            description: $book->getDescription(),
            authorIds: $book->getAuthors()->map(fn (Author $a): int => $a->getId())->toArray(),
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
