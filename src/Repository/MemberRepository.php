<?php

namespace App\Repository;

use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Member>
 */
class MemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
    }

    /**
     * @return array<Member>
     */
    public function getUnexpiredMembers(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.expirationDate >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<Member>
     */
    public function getExpiredMembers(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.expirationDate < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
