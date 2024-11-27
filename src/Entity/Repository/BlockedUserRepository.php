<?php
declare(strict_types=1);


namespace App\Entity\Repository;

use App\Entity\BlockedUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


final class BlockedUserRepository extends ServiceEntityRepository
{
    public function findAllBlocked(): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.unblockDetected IS NULL')
            ->getQuery()
            ->getResult();
    }

    public function save(BlockedUser $blockedUser)
    {
        $this->getEntityManager()->persist($blockedUser);
        $this->getEntityManager()->flush();
    }

    public function delete(BlockedUser $blockedUser)
    {
        $this->getEntityManager()->remove($blockedUser);
        $this->getEntityManager()->flush();
    }
}