<?php
declare(strict_types=1);


namespace App\Entity\Repository;

use App\Entity\Announcement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Criteria;


final class AnnouncementRepository extends ServiceEntityRepository
{
    /** @return Announcement[] */
    public function findAll(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.created', Criteria::DESC)
            ->getQuery()
            ->getResult();
    }

    /** @return Announcement[] */
    public function findAllThatShouldBeSent(): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.send <= :date')
            ->setParameter('date', new \DateTimeImmutable('now'))
            ->getQuery()
            ->getResult();
    }

    public function save(Announcement $announcement)
    {
        $this->getEntityManager()->persist($announcement);
        $this->getEntityManager()->flush();
    }

    public function delete(Announcement $announcement)
    {
        $this->getEntityManager()->remove($announcement);
        $this->getEntityManager()->flush();
    }
}