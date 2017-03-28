<?php

namespace AppBundle\Entity;


use Doctrine\ORM\EntityRepository;

class InventoryAlertRepository extends EntityRepository
{
    public function hasAlert(InventoryAlert $inventoryAlert)
    {
        $queryResult = $this->getEntityManager()->createQueryBuilder()
            ->select('sum(bsc.count) bscs, sum(tid.quantity) tids')
            ->from('AppBundle:Bin', 'b')
            ->join('b.skuCounts', 'bsc')
            ->join('b.travelerIds', 'tid')
            ->where('b.department = :department')
            ->andWhere('bsc.sku = :sku')
            ->andWhere('tid.sku = :sku')
            ->setParameter('department', $inventoryAlert->getDepartment())
            ->setParameter('sku', $inventoryAlert->getSku())
            ->getQuery()
            ->getSingleResult();

        $result = false;
        $count = $queryResult['bscs'] + $queryResult['tids'];
        switch ($inventoryAlert->getType()) {
            case InventoryAlert::TYPE_GREATER_THAN:
                if( $count > $inventoryAlert->getCount() ){
                    $result = $count;
                }
                break;
            default:
                if( $count < $inventoryAlert->getCount() ){
                    $result = $count;
                }
                break;
        }
        return $result;

    }
}