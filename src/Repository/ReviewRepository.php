<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    public function findReviewsByMovie(string $movieId, int $page, int $perPage): Paginator
    {
        $queryBuilder = $this
            ->createQueryBuilder('r')
            ->andWhere('r.movie = :movieId')
            ->setParameter('movieId', $movieId)
            ->andWhere('r.title IS NOT NULL')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($queryBuilder, false);
        $paginator->getQuery()->getResult();

        return $paginator;
    }
}
