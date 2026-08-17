<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * @return array{items: Book[], total: int}
     */
    public function findPaginated(
        ?string $title = null,
        string $sort = 'id',
        string $order = 'asc',
        int $page = 1,
        int $pageSize = 20,
    ): array {
        $qb = $this->createQueryBuilder('b');

        if ($title !== null) {
            $qb->andWhere('b.title LIKE :title')
                ->setParameter('title', '%'.$title.'%');
        }

        $total = (clone $qb)
            ->select('COUNT(b.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy('b.'.$sort, strtoupper($order))
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $items = $qb->getQuery()->getResult();

        return ['items' => $items, 'total' => (int) $total];
    }
}
