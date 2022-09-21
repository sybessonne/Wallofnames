<?php

namespace App\Repository;

use App\Entity\DiscountCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DiscountCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscountCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscountCode[]    findAll()
 * @method DiscountCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscountCode::class);
    }

    public function verifyDiscountCode(string $code, bool $totalCheck): ?DiscountCode
    {
        $query = $this->createQueryBuilder('dc')
            ->andWhere('dc.code = :myCode')
            ->setParameter('myCode', $code);

        if($totalCheck)
        {
            $query = $query->andWhere("dc.endDate >= CURRENT_DATE()")
                    ->andWhere('dc.nbUses > 0');
        }

        return $query->getQuery()
            ->getOneOrNullResult();
    }

    // /**
    //  * @return DiscountCode[] Returns an array of DiscountCode objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DiscountCode
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
