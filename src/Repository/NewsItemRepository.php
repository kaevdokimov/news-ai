<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\NewsItem;
use App\Entity\NewsSource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsItem>
 */
class NewsItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsItem::class);
    }

    public function findByGuidAndSource(string $guid, NewsSource $source): ?NewsItem
    {
        return $this->createQueryBuilder('ni')
            ->where('ni.guid = :guid')
            ->andWhere('ni.source = :source')
            ->setParameter('guid', $guid)
            ->setParameter('source', $source)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return NewsItem[]
     */
    public function findLatestNews(int $limit = 50): array
    {
        return $this->createQueryBuilder('ni')
            ->join('ni.source', 'ns')
            ->where('ns.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('ni.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return NewsItem[]
     */
    public function findBySource(NewsSource $source, int $limit = 50): array
    {
        return $this->createQueryBuilder('ni')
            ->where('ni.source = :source')
            ->setParameter('source', $source)
            ->orderBy('ni.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(NewsItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NewsItem $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
