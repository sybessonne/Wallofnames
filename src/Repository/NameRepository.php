<?php

Namespace App\Repository;

use App\Entity\Name;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Name|null find($id, $lockMode = null, $lockVersion = null)
 * @method Name|null findOneBy(array $criteria, array $orderBy = null)
 * @method Name[]    findAll()
 * @method Name[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Name::class);
    }

    // récupère les noms qui ont été confirmer par le paiement et qui n'ont pas été supprimé 
    // et qui sont encore en période d'affichage
    public function findValidatedNames()
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  
            ->innerJoin('n.order', 'o')

            ->andWhere('n.deletion = 0')
            ->andWhere('n.confirmation = 1')
            ->andWhere('o.paid = 1')
            ->andWhere("CURRENT_TIME() <= DATE_ADD(n.confirmationDate, d.nbDays, 'day')")
            ->andWhere("g.type = 'Normal'")

            ->getQuery()
            ->getResult();
    }

    //récupère les noms qui ont été ajouté en base de donnée
    //mais qui n'ont pas forcément été encore confirmer par le paiement (d'où le confirmation = 0)
    public function findAllNames()
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  

            ->andWhere('n.deletion = 0')
            ->andWhere("(CURRENT_TIME() <= DATE_ADD(n.confirmationDate, d.nbDays, 'day') or n.confirmation = 0)")
            ->andWhere("g.type = 'Normal'")
            ->orderBy('n.positionY')
            ->orderBy('n.positionX')

            ->getQuery()
            ->getResult();
    }

    //Vérifie si la position est bien libre ou non
    public function findIfPlaceFree(Name $name)
    {
        return $this->createQueryBuilder('n')
            ->select('COUNT(n)')

            ->innerJoin('n.textFont', 'p')  
            ->innerJoin('n.size', 't')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  

            ->andWhere('n.deletion = 0')
            ->andWhere("(CURRENT_TIME() <= DATE_ADD(n.confirmationDate, d.nbDays, 'day') or n.confirmation = 0)")
            ->andWhere("g.type = 'Normal'")
            ->andWhere('not ((:pX >= n.positionX + n.width) 
                            or (:pX + :w <= n.positionX)
                            or (:pY >= n.positionY + n.height)
                            or (:pY + :h <= n.positionY))')
            ->setParameter('pX', $name->getPositionX())
            ->setParameter('pY', $name->getPositionY())
            ->setParameter('w', $name->getWidth())
            ->setParameter('h', $name->getHeight())

            ->getQuery()
            ->getSingleScalarResult();
    }

    // récupère les noms qui n'ont pas été validés ou supprimés
    public function findNamesToValidate()
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  
            ->innerJoin('n.order', 'o')
            ->innerJoin('o.paymentMethod', 'pm')
            ->leftJoin('o.discountCode', 'dc')

            ->andWhere('n.validation = 0')
            ->andWhere('n.confirmation = 1')
            ->andWhere('n.deletion = 0')
            ->andWhere('o.paid = 1')

            ->getQuery()
            ->getResult();
    }

    // récupère les noms qui n'ont pas été validés ou supprimés pour les afficher sur le mur de la partie admin
    public function findNamesToValidateForWall()
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  

            ->andWhere('n.validation = 0')
            ->andWhere('n.deletion = 0')

            ->getQuery()
            ->getResult();
    }

    //Récupère un nom pour pouvoir le confirmer après que le paiement a bien été effectué
    // fonction appelée au niveau des webhooks
    public function findOneByPaymentId(string $paymentId): ?Name
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  
            ->innerJoin('n.order', 'o')  

            ->andWhere("o.paymentId = :pi")
            ->setParameter('pi', $paymentId)

            ->getQuery()
            ->getOneOrNullResult();
    }

    //Récupère un nom pour pouvoir le confirmer après que le paiement a bien été effectué
    // fonction appelée au niveau de PayPal webhook
    public function findOneByCaptureId(string $captureId): ?Name
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  
            ->innerJoin('n.order', 'o')  

            ->andWhere("o.payPalCaptureId = :ci")
            ->setParameter('ci', $captureId)

            ->getQuery()
            ->getOneOrNullResult();
    }

    //Récupère un nom pour pouvoir le confirmer après que le paiement a bien été effectué
    // fonction appelée au niveau de PayPal webhook
    public function findOneByRefundId(string $refundId): ?Name
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  
            ->innerJoin('n.order', 'o')  

            ->andWhere("o.payPalRefundId = :ri")
            ->setParameter('ri', $refundId)

            ->getQuery()
            ->getOneOrNullResult();
    }

    // Fonction qui récupère le nom en fonction de son lien de reçu
    // permet de récupérer le nom pour afficher le recu de paiement dans le navigateur
    public function findOneByReceipt(string $receipt): ?Name
    {
        return $this->createQueryBuilder('n')
            ->innerJoin('n.textFont', 't')  
            ->innerJoin('n.size', 's')  
            ->innerJoin('n.delay', 'd')  
            ->innerJoin('n.grade', 'g')  
            ->innerJoin('n.order', 'o')  

            ->andWhere("o.receipt = :r")
            ->setParameter('r', $receipt)

            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findNamesNotPaidInTime(int $time)
    {
        return $this->createQueryBuilder('n')
            ->where('n.deletion = 0')
            ->andWhere('n.confirmation = 0')
            ->andWhere("DATE_ADD(n.addedDate, :t, 'minute') < CURRENT_TIME()")
            ->setParameter('t', $time)
            ->getQuery()
            ->getResult();
    }
}
