<?php

namespace App\Entity\Repository;

use App\Entity\Channel;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;

class ChannelRepository extends ServiceEntityRepository
{
    public function findForUser(User $user): array|Collection
    {
        if ($user->isSuperAdmin() || $user->isAdmin())
            return $this->findAll();

        if ($user->hasAccessToAllChannels())
            return $this->findAllExceptTesting();

        return $user->getChannels();
    }

    public function findAllExceptTesting(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.Name != :testing')
            ->setParameter('testing', 'testing')
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Channel
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.Name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByIdentifier(string $identifier): ?Channel
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(Channel $channel)
    {
        $this->getEntityManager()->persist($channel);
        $this->getEntityManager()->flush();
    }

    public function delete(Channel $channel)
    {
        $this->getEntityManager()->remove($channel);
        $this->getEntityManager()->flush();
    }
}