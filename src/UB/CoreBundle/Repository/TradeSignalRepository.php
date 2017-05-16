<?php

namespace UB\CoreBundle\Repository;
/**
 * SignalRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TradeSignalRepository extends \Doctrine\ORM\EntityRepository
{
       public function getLastSignals()
       {
           $lastSignals = $this->findBy(
                                    array('isTrade' => 0)
                   );
           return $lastSignals;
       }    
       
       public function isSignalAlreadyTradeInSameMinute(\UB\CoreBundle\Entity\TradeSignal $signal) {          
        $qb = $this->createQueryBuilder('ts');
        $res = $qb->select('ts')
                ->Where('YEAR(ts.startTime) = YEAR(:startTimeSig)')
                ->andWhere('MONTH(ts.startTime) = MONTH(:startTimeSig)')
                ->andWhere('DAY(ts.startTime) = DAY(:startTimeSig)')
                ->andWhere('HOUR(ts.startTime) = DAY(:startTimeSig)')
                ->andWhere('MINUTE(ts.startTime) = MINUTE(:startTimeSig)')
                ->setParameter('startTimeSig', $signal->getStartTime())
                ->andWhere('ts.symbole = :SymboleSig')
                ->setParameter('SymboleSig', $signal->getSymbole())
                ->andWhere('ts.contractType = :contractTypeSig')
                ->setParameter('contractTypeSig', $signal->getContractType())
                ->andWhere('ts.id <> :id')
                ->setParameter('id', $signal->getId())
                ->getQuery()
                ->getResult();

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }

}