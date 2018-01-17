<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of TradeSignalPersister
 *
 * @author David
 */
use UB\CoreBundle\Entity\TradeSignal;
use UB\CoreBundle\Repository\TradeSignalRepository;
use UB\CoreBundle\Repository\TradeRepository;
use Doctrine\ORM\EntityManager;
use UB\CoreBundle\Entity\Trade;
use UB\CoreBundle\Entity\Parameter;

class TradeSignalPersister {

    private $em;
    private $tradeSignalRepo;
    private $tradeRepository;

    public function __construct(EntityManager $em, TradeSignalRepository $tradeSignalRepo, TradeRepository $tradeRepository) {

        $this->em = $em;
        $this->tradeSignalRepo = $tradeSignalRepo;
        $this->tradeRepository = $tradeRepository;
    }

    public function persist(TradeSignal $tradeSignal) {
        $this->em->persist($tradeSignal);
        $this->em->flush();
    }

    public function randomSignal($symbole, $categSignal, Trade $trade, Parameter $parameter) {
        $tradeSignal = $this->tradeSignalRepo->getLastEntity();
        $signal = new TradeSignal();
        $signal->setSymbole($symbole);
        $signal->setStartTime(new \DateTime());
        $signal->setDuration(5);
        $signal->setIsTrade(0);
        $signal->setCategorySignal($categSignal);
        $signal->setName($categSignal->getName());

        $lastTwoTrade = $this->tradeRepository->getLastTwoTrade();
        $nbLoose = 0;
        $sens = '';
        
        if ($parameter->getTendance() && $trade->getState() == Trade::STATELOOSE){
            $parameter->setTendance(0);
        }
       
        foreach ($lastTwoTrade as $tmpTrade) {
            if ($tmpTrade->getState() == 'LOOSE') {
                $nbLoose ++;
                $sens = $tmpTrade->getContractType();
            }
            if ($nbLoose == 2 && $sens == $tmpTrade->getContractType()) {
                $parameter->setTendance(1);
                if ($tmpTrade->getContractType() == 'CALL') {
                    $parameter->setTendance(1); //$inverse = 'PUT';
                } else {
                    $parameter->setTendance(2); //$inverse = 'CALL';
                }
            }
        }
        
        // mode tendance
        /*
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
         * 
         */

        // Mode inversé
        if ($parameter->getTendance()) {
            if ($parameter->getTendance() == 1) {
                $signal->setContractType('PUT');
            } elseif ($parameter->getTendance() == 2) {
                $signal->setContractType('CALL');
            }
        } elseif ($trade->getContractType() == 'CALL' && $trade->getState() == Trade::STATELOOSE) {
            $signal->setContractType('CALL');
        } else if ($trade->getContractType() == 'PUT' && $trade->getState() == Trade::STATELOOSE) {
            $signal->setContractType('PUT');
        } else if ($trade->getContractType() == 'CALL' && $trade->getState() == Trade::STATEWIN) {
            $signal->setContractType('PUT');
        } else {
            $signal->setContractType('CALL');
        }

$signal->setContractType('PUT');
        
        /*
          if (mt_rand(0, 99) < 50) {
          $signal->setContractType('CALL');
          } else {
          $signal->setContractType('PUT');
          } */

        $this->persist($signal);
    }

    public function tendanceSignal($symbole, $categSignal, $tendance) {
        $signal = new TradeSignal();
        $signal->setSymbole($symbole);
        $signal->setStartTime(new \DateTime());
        $signal->setDuration(1);
        $signal->setIsTrade(0);
        $signal->setCategorySignal($categSignal);
        $signal->setName($categSignal->getName());

        if ($tendance == 1) {
            $signal->setContractType('CALL');
        } else if ($tendance == 0) {
            $signal->setContractType('PUT');
        }
        $this->persist($signal);
    }

}
