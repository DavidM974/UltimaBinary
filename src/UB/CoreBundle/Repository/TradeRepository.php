<?php

namespace UB\CoreBundle\Repository;
use UB\CoreBundle\Entity\Sequence;
/**
 * TradeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TradeRepository extends \Doctrine\ORM\EntityRepository
{
    // créer une requete qui récupére la plus grande mise du jour
    // créer une fonction qui retourne des statr sur un nombre de trade
    
    //
    public function getNewTrades() {
        
        $listTrades = $this->findBy(array ('state'=>'WAIT'));
        return $listTrades;
    }
    
        // Récupérer la dernière séquence ouverte
    public function getTradeForSequence($sequence) {
        $trades =  $this->findBy(
            array ('sequence'=> $sequence),
            array ('signalTime'=>'ASC')
            );
        return $trades;

    }

    function getLastTrade(Sequence $sequence = NULL) {
        $qb = $this->createQueryBuilder('t');
        if ($sequence != NULL) {
            $qb->Where('t.sequence = :seq')
            ->setParameter('seq', $sequence);
        }
        return $qb->orderBy('t.signalTime', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
    }
    
function getLastWinTrade(Sequence $sequence = NULL) {
        $qb = $this->createQueryBuilder('t');
        if ($sequence != NULL) {
            $qb->Where('t.sequence = :seq')
                    ->setParameter('seq', $sequence)
                    ->andWhere('tr.state = :state')
                    ->setParameter('state', \UB\CoreBundle\Entity\Trade::STATEWIN);
        }
        return $qb->orderBy('t.signalTime', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    function getLastLooseTrade(Sequence $sequence = NULL) {
        $qb = $this->createQueryBuilder('t');
        if ($sequence != NULL) {
            $qb->Where('t.sequence = :seq')
                    ->setParameter('seq', $sequence)
                    ->andWhere('tr.state = :state')
                    ->setParameter('state', \UB\CoreBundle\Entity\Trade::STATELOOSE)
                    ->andWhere('tr.sequenceState = :seqState')
                    ->setParameter('seqState', \UB\CoreBundle\Entity\Trade::SEQSTATEUNDONE);
        }
        return $qb->orderBy('t.signalTime', 'DESC')
                        ->setMaxResults(1)
                        ->getQuery()
                        ->getOneOrNullResult();
    }

    public function getUndoneTrade($sequence) {
         $qb = $this->createQueryBuilder('tr');
 
        $result = $qb->select('tr')
            ->Where('tr.sequenceState <> :state')
            ->setParameter('state', \UB\CoreBundle\Entity\Trade::SEQSTATEDONE)
            ->andWhere('tr.sequence = :seq')
            ->setParameter('seq', $sequence)
            ->addOrderBy('tr.signalTime', 'ASC')
            ->getQuery()
            ->getResult(); 
        return $result;
    }
    
        public function getUndoneTradeOrNull($sequence) {
         $qb = $this->createQueryBuilder('tr');
 
        $result = $qb->select('tr')
            ->Where('tr.sequenceState <> :state')
            ->setParameter('state', \UB\CoreBundle\Entity\Trade::SEQSTATEDONE)
            ->andWhere('tr.sequence = :seq')
            ->setParameter('seq', $sequence)
            ->addOrderBy('tr.signalTime', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
        return $result;
    }
    
    public function isTrading() {
        $qb = $this->createQueryBuilder('tr')
                ->Where('tr.state = :state')
                ->setParameter('state', \UB\CoreBundle\Entity\Trade::STATETRADE)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        return $qb;
    }

    public function getLooseTrades($sequence) {
        $qb = $this->createQueryBuilder('tr');

        $result = $qb->select('tr')
                ->Where('tr.state = :state')
                ->setParameter('state', \UB\CoreBundle\Entity\Trade::STATELOOSE)
                ->andWhere('tr.sequence = :seq')
                ->setParameter('seq', $sequence)
                ->addOrderBy('tr.signalTime', 'ASC')
                ->getQuery()
                ->getResult();
        return $result;
    }

    public function getSumWinSequence($sequence) {
        return $this->createQueryBuilder('tr')
                        ->Where('tr.state = :state')
                        ->setParameter('state', \UB\CoreBundle\Entity\Trade::STATEWIN)
                        ->andWhere('tr.sequence = :seq')
                        ->setParameter('seq', $sequence)
                        ->select('SUM(tr.amountRes) as sumLoose')
                        ->getQuery()
                        ->getSingleScalarResult();
    }

    public function getSubQuerySequenceTrading() {
         $subqueryBuilder = $this->createQueryBuilder('tr');
 
        $subquery = $subqueryBuilder->select('tr.sequence')
            ->Where('tr.state <> :state')
            ->setParameter('state', \UB\CoreBundle\Entity\Trade::STATETRADE)
            ->distinct('tr.sequence'); 
        return $subquery;
    }
    
           public function isAlreadyTradeInSameMinute(\UB\CoreBundle\Entity\TradeSignal $signal) {          
        $qb = $this->createQueryBuilder('tr');
        $res = $qb->select('tr')
                ->Where('YEAR(tr.signalTime) = YEAR(:signalTimeSig)')
                ->andWhere('MONTH(tr.signalTime) = MONTH(:signalTimeSig)')
                ->andWhere('DAY(tr.signalTime) = DAY(:signalTimeSig)')
                ->andWhere('HOUR(tr.signalTime) = HOUR(:signalTimeSig)')
                ->andWhere('MINUTE(tr.signalTime) = MINUTE(:signalTimeSig)')
                ->setParameter('signalTimeSig', $signal->getStartTime())
                ->andWhere('tr.symbole = :SymboleSig')
                ->setParameter('SymboleSig', $signal->getSymbole())
                ->andWhere('tr.contractType = :contractTypeSig')
                ->setParameter('contractTypeSig', $signal->getContractType())
                ->getQuery()
                ->getResult();

        if (empty($res)) {
            return false;
        } else {
            return true;
        }
    }
}
