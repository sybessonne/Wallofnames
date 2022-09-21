<?php

namespace App\Repository;

use App\Entity\TextFont;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TextFont|null find($id, $lockMode = null, $lockVersion = null)
 * @method TextFont|null findOneBy(array $criteria, array $orderBy = null)
 * @method TextFont[]    findAll()
 * @method TextFont[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextFontRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TextFont::class);
    }

    public function findAll()
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.textFont')

            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return TextFont[] Returns an array of TextFont objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TextFont
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
