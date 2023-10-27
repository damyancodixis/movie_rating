<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function findPaginated(int $page, int $perPage, string $title = null): Paginator
    {
        $queryBuilder = $this
            ->createQueryBuilder('m');

        if ($title) {
            $queryBuilder
                ->andWhere('LOWER(m.title) LIKE :title')
                ->setParameter('title', '%' . strtolower($title) . '%');
        }

        $queryBuilder
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($queryBuilder, false);
        $paginator->getQuery()->getResult();

        return $paginator;
    }
}
