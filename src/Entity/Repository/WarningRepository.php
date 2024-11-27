<?php
declare(strict_types=1);


namespace App\Entity\Repository;

use App\Entity\Warning;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;


class WarningRepository extends ServiceEntityRepository
{
    public function findMostRecent(): array
    {
        return $this->createQueryBuilder('w')
            ->orderBy('w.created', Criteria::DESC)
            ->andWhere('w.created > :date')
            ->setParameter('date', new \DateTimeImmutable('-1 week', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    public function findUsernameHistory(string $username): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.userName = :username')
            ->andWhere('w.created > :date')
            ->setParameter('username', $username)
            ->setParameter('date', new \DateTimeImmutable('-3 months', new \DateTimeZone('UTC')))
            ->orderBy('w.created', Criteria::DESC)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function countInPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.created >= :start')
            ->andWhere('w.created <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countInPeriodForUsername(\DateTimeImmutable $start, \DateTimeImmutable $end, string $username): int
    {
        return $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->where('w.created >= :start')
            ->andWhere('w.created <= :end')
            ->andWhere('w.userName = :username')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('username', $username)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findMostPopularChannels(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.channelId, COUNT(w.id) as count')
            ->where('w.created > :date')
            ->andWhere('w.channelId IS NOT NULL')
            ->setParameter('date', new \DateTimeImmutable('-1 month', new \DateTimeZone('UTC')))
            ->groupBy('w.channelId')
            ->orderBy('count', Criteria::DESC)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function findMostPopularUsers(): array
    {
        return $this->createQueryBuilder('w')
            ->select('w.userName, COUNT(w.id) as count')
            ->where('w.created > :date')
            ->andWhere('w.userName IS NOT NULL')
            ->setParameter('date', new \DateTimeImmutable('-1 month', new \DateTimeZone('UTC')))
            ->groupBy('w.userName')
            ->orderBy('count', Criteria::DESC)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function save(Warning $warning)
    {
        $this->getEntityManager()->persist($warning);
        $this->getEntityManager()->flush();
    }

    public function delete(Warning $warning)
    {
        $this->getEntityManager()->remove($warning);
        $this->getEntityManager()->flush();
    }
}