<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NewsSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsSource>
 */
class NewsSourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsSource::class);
    }

    /**
     * @return NewsSource[]
     */
    public function findActiveSources(): array
    {
        return $this->createQueryBuilder('ns')
            ->where('ns.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ns.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(NewsSource $newsSource, bool $flush = false): void
    {
        $this->getEntityManager()->persist($newsSource);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NewsSource $newsSource, bool $flush = false): void
    {
        $this->getEntityManager()->remove($newsSource);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
