<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of TradePersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Trade;
use UB\CoreBundle\Repository\TradeRepository;
use Doctrine\ORM\EntityManager;


class TradePersister 
{
    private $em;
    private $tradeRepo;
    
    public function __construct(EntityManager $em, TradeRepository $tradeRepo) {
        
        $this->em = $em;
        $this->tradeRepo = $tradeRepo;
    }
    
    public function persist(Trade $trade)
    {
        $this->em->persist($trade);
        $this->em->flush();
    }
    
    public function newTradeIntercale($amount, Trade $lastTrade, $sequence) {
        $trade = new Trade();
        if ($lastTrade->getContractType() == 'CALL'){
            $sens = 'PUT';
        } else {
            $sens = 'CALL';
        }
        $trade->setAmount($amount);
        $trade->setSymbole($lastTrade->getSymbole());
        $trade->setDuration($lastTrade->getDuration());
        $trade->setCurrency($lastTrade->getCurrency());
        $trade->setContractType($sens);
        $trade->setState(Trade::STATELOOSE);
        
        $trade->setSequenceState(Trade::SEQSTATEUNDONE);
        
        $trade->setSequence($sequence);
        $trade->setIdBinary(random_int(100000, 1000000));
        $trade->setSignalTime(new \DateTime());
        //$trade->setCategorySignal($lastTrade->getCategorySignal());
        
        //sauvegarde les données
        $this->em->persist($trade);
        //Update BDD
        $this->em->flush();
        return $trade;
    }
}
