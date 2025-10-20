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

    public function findByGuidAndSource(string $guid, NewsSource $newsSource): ?NewsItem
    {
        return $this->createQueryBuilder('ni')
            ->where('ni.guid = :guid')
            ->andWhere('ni.source = :source')
            ->setParameter('guid', $guid)
            ->setParameter('source', $newsSource)
            ->getQuery()
            ->getOneOrNullResult()
        ;
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
            ->getResult()
        ;
    }

    /**
     * @return NewsItem[]
     */
    public function findBySource(NewsSource $newsSource, int $limit = 50): array
    {
        return $this->createQueryBuilder('ni')
            ->where('ni.source = :source')
            ->setParameter('source', $newsSource)
            ->orderBy('ni.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(NewsItem $newsItem, bool $flush = false): void
    {
        $this->getEntityManager()->persist($newsItem);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NewsItem $newsItem, bool $flush = false): void
    {
        $this->getEntityManager()->remove($newsItem);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
