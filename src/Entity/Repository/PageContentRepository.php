<?php
declare(strict_types=1);


namespace App\Entity\Repository;

use App\Entity\PageContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


final class PageContentRepository extends ServiceEntityRepository
{
    public function findAll(): array
    {
        return $this->createQueryBuilder('pc')
            ->getQuery()
            ->getResult();
    }

    public function findByIdentifier(string $identifier): ?PageContent
    {
        return $this->createQueryBuilder('pc')
            ->andWhere('pc.identifier = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function save(PageContent $pageContent)
    {
        $this->getEntityManager()->persist($pageContent);
        $this->getEntityManager()->flush();
    }

    public function delete(PageContent $pageContent)
    {
        $this->getEntityManager()->remove($pageContent);
        $this->getEntityManager()->flush();
    }
}