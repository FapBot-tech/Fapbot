<?php
declare(strict_types=1);


namespace App\Entity\Repository;

use App\Entity\Reason;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


class ReasonRepository extends ServiceEntityRepository
{
    public function save(Reason $reason)
    {
        $this->getEntityManager()->persist($reason);
        $this->getEntityManager()->flush();
    }

    public function delete(Reason $reason)
    {
        $this->getEntityManager()->remove($reason);
        $this->getEntityManager()->flush();
    }
}