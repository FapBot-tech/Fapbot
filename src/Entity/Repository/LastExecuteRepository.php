<?php

namespace App\Entity\Repository;

use App\Entity\Channel;
use App\Entity\LastExecute;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;

class LastExecuteRepository extends ServiceEntityRepository
{
    public function getLastExecuteForIdentifier(string $identifier): ?LastExecute
    {
        return $this->createQueryBuilder('e')
            ->where('e.identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->orderBy('e.lastRun', Criteria::DESC)
            ->setmaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
    public function save(LastExecute $lastExecute)
    {
        $this->purge();

        $this->getEntityManager()->persist($lastExecute);
        $this->getEntityManager()->flush();
    }

    public function delete(LastExecute $lastExecute)
    {
        $this->getEntityManager()->remove($lastExecute);
        $this->getEntityManager()->flush();
    }

    public function purge(): void
    {
        $this->createQueryBuilder('e')
            ->delete()
            ->where('e.lastRun < :date')
            ->setParameter('date', new \DateTimeImmutable('-2 weeks', new \DateTimeZone('UTC')))
            ->getQuery()
            ->execute();
    }
}