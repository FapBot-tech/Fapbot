<?php

namespace App\Entity\Repository;

use App\Entity\Channel;
use App\Entity\Mute;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;

class MuteRepository extends ServiceEntityRepository
{
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.created < :now')
            ->andWhere('m.endTime > :now')
            ->andWhere('m.active = 1')
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->orderBy('m.endTime', Criteria::ASC)
            ->getQuery()
            ->getResult();
    }

    public function findReadyForUnmute(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.endTime < :now')
            ->andWhere('m.active = 1')
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    public function findUsernameHistory(string $username): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.userName = :username')
            ->andWhere('m.created > :date OR m.endTime > :now')
            ->setParameter('username', $username)
            ->setParameter('date', new \DateTimeImmutable('-3 months', new \DateTimeZone('UTC')))
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->orderBy('m.created', Criteria::DESC)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function countInPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.created >= :start')
            ->andWhere('m.created <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countInPeriodForUsername(\DateTimeImmutable $start, \DateTimeImmutable $end, string $username)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.created >= :start')
            ->andWhere('m.created <= :end')
            ->andWhere('m.userName = :username')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('username', $username)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findMostPopularUsers(): array
    {
        return $this->createQueryBuilder('m')
            ->select('m.userName, COUNT(m.id) as count')
            ->where('m.created > :date')
            ->setParameter('date', new \DateTimeImmutable('-1 month', new \DateTimeZone('UTC')))
            ->groupBy('m.userName')
            ->orderBy('count', Criteria::DESC)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
    }

    public function countMutesInChannel(Channel $channel): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->innerJoin('m.channels', 'c')
            ->where('m.created > :date')
            ->andWhere('c.id = :channelId')
            ->setParameter('date', new \DateTimeImmutable('-1 month', new \DateTimeZone('UTC')))
            ->setParameter('channelId', $channel->getId())
            ->setMaxResults(10)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUserMutesInChannel(Channel $channel, string $username): array
    {
        return $this->createQueryBuilder('m')
            ->innerJoin('m.channels', 'c')
            ->where('m.userName = :username')
            ->andWhere('c.id = :channelId')
            ->andWhere('m.endTime > :now')
            ->andWhere('m.active = 1')
            ->setParameter('username', $username)
            ->setParameter('channelId', $channel->getId())
            ->setParameter('now', new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->getQuery()
            ->getResult();
    }

    public function save(Mute $mute)
    {
        $this->getEntityManager()->persist($mute);
        $this->getEntityManager()->flush();
    }

    public function delete(Mute $mute)
    {
        $this->getEntityManager()->remove($mute);
        $this->getEntityManager()->flush();
    }
}