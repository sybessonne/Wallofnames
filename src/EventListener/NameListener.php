<?php

namespace App\EventListener;
 
use App\Entity\Name;
use Doctrine\ORM\Event\OnFlushEventArgs;

use App\Exception\NamePositionException;

use Symfony\Component\Config\Definition\Exception\Exception;

class NameListener
{
    public function onFlush(OnFlushEventArgs $args)
    {
        $em = $args->getEntityManager();
        $uow = $em->getUnitOfWork();
 
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Name) {
                $nn = intval($em->getRepository(Name::class)->findIfPlaceFree($entity));

                if($nn === 0){
                    $uow->computeChangeSet($em->getClassMetadata(get_class($entity)), $entity);
                }
                else{
                    throw new NamePositionException("Place not free");
                }
            }
        }
    }
}
