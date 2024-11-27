<?php
declare(strict_types=1);

namespace App\Entity\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


final class ReportRepository extends ServiceEntityRepository
{
    public function save(Report $mute)
    {
        $this->getEntityManager()->persist($mute);
        $this->getEntityManager()->flush();
    }

    public function delete(Report $mute)
    {
        $this->getEntityManager()->remove($mute);
        $this->getEntityManager()->flush();
    }
}