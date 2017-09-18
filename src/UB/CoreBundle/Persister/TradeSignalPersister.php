<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of TradeSignalPersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\TradeSignal;
use UB\CoreBundle\Repository\TradeSignalRepository;
use Doctrine\ORM\EntityManager;


class TradeSignalPersister 
{
    private $em;
    private $tradeSignalRepo;
    
    public function __construct(EntityManager $em, TradeSignalRepository $tradeSignalRepo) {
        
        $this->em = $em;
        $this->tradeSignalRepo = $tradeSignalRepo;
    }
    
    
    
    
    public function persist(TradeSignal $tradeSignal)
    {
        $this->em->persist($tradeSignal);
        $this->em->flush();
    }
    
    
    public function randomSignal($symbole, $categSignal, \UB\CoreBundle\Entity\Trade $trade)
    {
    $tradeSignal = $this->tradeSignalRepo->getLastEntity();
        $signal = new TradeSignal();
        $signal->setSymbole($symbole);
        $signal->setStartTime(new \DateTime());
        $signal->setDuration(5);
        $signal->setIsTrade(0);
        $signal->setCategorySignal($categSignal);
        $signal->setName($categSignal->getName());
        
        if ($trade->getContractType() == 'PUT' AND $trade->getState() == \UB\CoreBundle\Entity\Trade::STATELOOSE)
        {
            $signal->setContractType('CALL');
        } else if($trade->getContractType() == 'PUT') {
            $signal->setContractType('PUT');
        }
        if ($trade->getContractType() == 'CALL' AND $trade->getState() == \UB\CoreBundle\Entity\Trade::STATELOOSE)
        {
            $signal->setContractType('PUT');
        } else {
            $signal->setContractType('CALL');
        }
        
        
        /*
        if (mt_rand(0, 99) < 50) {
            $signal->setContractType('CALL');
        } else {
            $signal->setContractType('PUT');
        }*/
        
            $this->persist($signal);

    }
    
    
    public function tendanceSignal($symbole, $categSignal, $tendance)
    {
        $signal = new TradeSignal();
        $signal->setSymbole($symbole);
        $signal->setStartTime(new \DateTime());
        $signal->setDuration(1);
        $signal->setIsTrade(0);
        $signal->setCategorySignal($categSignal);
        $signal->setName($categSignal->getName());
        
        if ($tendance == 1)
        {
            $signal->setContractType('CALL');
        } else if($tendance == 0){
            $signal->setContractType('PUT');
        }
        $this->persist($signal);
    }
}
