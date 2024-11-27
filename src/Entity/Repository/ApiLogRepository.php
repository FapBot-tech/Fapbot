<?php
declare(strict_types=1);


namespace App\Entity\Repository;

use App\Entity\ApiLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache;
use Doctrine\Persistence\ManagerRegistry;


final class ApiLogRepository extends ServiceEntityRepository
{
    private int $cacheLifespan;

    public function __construct(ManagerRegistry $registry, string $entityClass, int $cacheLifespan = 3600)
    {
        parent::__construct($registry, $entityClass);
        $this->cacheLifespan = $cacheLifespan;
    }

    public function getSuccessfulCount(): int
    {
        $date = new \DateTimeImmutable('-1 week', new \DateTimeZone('UTC'));
        $date = $date->setTime((int) $date->format('H'), 0, 0);

        return $this->createQueryBuilder('l')
            ->select('COUNT(l)')
            ->where('l.success = True')
            ->andWhere('l.created > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->enableResultCache($this->cacheLifespan, 'success_log_count')
            ->getSingleScalarResult();
    }

    public function getFailureCount(): int
    {
        $date = new \DateTimeImmutable('-1 week', new \DateTimeZone('UTC'));
        $date = $date->setTime((int) $date->format('H'), 0, 0);

        return $this->createQueryBuilder('l')
            ->select('COUNT(l)')
            ->where('l.success = False')
            ->andWhere('l.created > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->enableResultCache($this->cacheLifespan, 'error_log_count')
            ->getSingleScalarResult();
    }

    /** @return ApiLog[] */
    public function getSuccessLogs(): array
    {
        $date = new \DateTimeImmutable('-1 week', new \DateTimeZone('UTC'));
        $date = $date->setTime((int) $date->format('H'), 0, 0);

        return $this->createQueryBuilder('l')
            ->where('l.success = True')
            ->andWhere('l.created > :date')
            ->setMaxResults(5)
            ->setParameter('date', $date)
            ->orderBy('l.created', Criteria::DESC)
            ->getQuery()
            ->enableResultCache(600, 'success_logs')
            ->getResult();
    }

    /** @return ApiLog[] */
    public function getFailLogs(): array
    {
        $date = new \DateTimeImmutable('-1 week', new \DateTimeZone('UTC'));
        $date = $date->setTime((int) $date->format('H'), 0, 0);

        return $this->createQueryBuilder('l')
            ->where('l.success = False')
            ->andWhere('l.created > :date')
            ->setMaxResults(5)
            ->setParameter('date', $date)
            ->orderBy('l.created', Criteria::DESC)
            ->getQuery()
            ->enableResultCache(600, 'error_logs')
            ->getResult();
    }

    public function save(ApiLog $log): ApiLog
    {
        if ($log->getExtractedError() === 'Chat could not be reached')
            return $log;

        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();

        return $log;
    }

    public function delete(ApiLog $log): void
    {
        $this->getEntityManager()->remove($log);
        $this->getEntityManager()->flush();
    }

    public function purge(): void
    {
        $this->createQueryBuilder('l')
            ->delete()
            ->where('l.created < :date')
            ->setParameter('date', new \DateTimeImmutable('-2 weeks', new \DateTimeZone('UTC')))
            ->getQuery()
            ->execute();
    }
}