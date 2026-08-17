<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Author;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Author>
 */
class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    /**
     * @return array{items: Author[], total: int}
     */
    public function findPaginated(
        ?string $name = null,
        string $sort = 'id',
        string $order = 'asc',
        int $page = 1,
        int $pageSize = 20,
    ): array {
        $qb = $this->createQueryBuilder('a');

        if ($name !== null) {
            $qb->andWhere('a.name LIKE :name')
                ->setParameter('name', '%'.$name.'%');
        }

        $total = (clone $qb)
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->orderBy('a.'.$sort, strtoupper($order))
            ->setFirstResult(($page - 1) * $pageSize)
            ->setMaxResults($pageSize);

        $items = $qb->getQuery()->getResult();

        return ['items' => $items, 'total' => (int) $total];
    }
}
