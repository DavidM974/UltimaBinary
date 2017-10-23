<?php

namespace UB\CoreBundle\Algo;

use UB\CoreBundle\Entity\Parameter;
use UB\CoreBundle\Entity\Trade;
use UB\CoreBundle\Entity\Sequence;
use UB\CoreBundle\Entity\TradeSignal;
use UB\CoreBundle\Entity\StaticWinJoker;
use UB\CoreBundle\Entity\Joker;
use UB\CoreBundle\Repository\ParameterRepository;
use UB\CoreBundle\Repository\TradeRepository;
use UB\CoreBundle\Repository\TransactionRepository;
use UB\CoreBundle\Repository\SequenceRepository;
use UB\CoreBundle\Repository\TradeSignalRepository;
use UB\CoreBundle\Repository\JokerRepository;
use UB\CoreBundle\Repository\StaticWinJokerRepository;
use UB\CoreBundle\Persister\TradePersister;
use UB\CoreBundle\Persister\SequencePersister;
use UB\CoreBundle\Persister\JokerPersister;
use UB\CoreBundle\Persister\StaticWinJokerPersister;
use UB\CoreBundle\Persister\TradeSignalPersister;
use UB\CoreBundle\Persister\ParameterPersister;
use Doctrine\ORM\EntityManager;

/**
 * Description of AlgoSequence
 *
 * @author David
 */
class UBAlgo {

    //Persister
    private $tradePersister;
    private $tradeSignalPersister;
    private $sequencePersister;
    private $jokerPersister;
    private $staticWinJokerPersister;
    private $parameterPersister;
    //repository
    private $parameterRepo;
    private $sequenceRepo;
    private $tradeRepo;
    private $signalRepo;
    private $transactionRepo;
    private $jokerRepo;
    private $staticWinJokerRepo;
    //variable
    private $entityManager;
    private $parameter;
    private $miseInitial;
    private $miseCourante;
    private $staticWinJoker;
    private $sequencesToExclude;
    private $winOrange;

    const DEFAULT_MISE = 0.50;
    const NB_SEQ = 1;

    const MODE_VERT = 0.025;
    const MODE_ORANGE = 0.05;
    const MODE_ROUGE = 0.1;
    

    public function getMiseInitial() {
        return $this->miseInitial;
    }

    public function setMiseInital($miseInital) {
        $this->miseInitial = $miseInital;
    }

    public function getMiseCourante() {
        return $this->miseCourante;
    }

    public function setMiseCourante($miseCourante) {
        $this->miseCourante = $miseCourante;
    }

    public function __construct(ParameterPersister $parameterPersister, TradePersister $tradePersister, SequencePersister $sequencePersister, JokerPersister $jokerPersister, StaticWinJokerPersister $staticWinJokerPersister, TradeSignalPersister $tradeSignalPersister, ParameterRepository $parameterRepo, TradeRepository $tradeRepo, TradeSignalRepository $signalRepo, TransactionRepository $transactionRepo, SequenceRepository $sequenceRpo, JokerRepository $jokerRepo, StaticWinJokerRepository $staticWinJokerRepo, EntityManager $em) {
        //Persister
        $this->tradePersister = $tradePersister;
        $this->sequencePersister = $sequencePersister;
        $this->jokerPersister = $jokerPersister;
        $this->staticWinJokerPersister = $staticWinJokerPersister;
        $this->tradeSignalPersister = $tradeSignalPersister;
        $this->parameterPersister = $parameterPersister;

        // repo init
        $this->parameterRepo = $parameterRepo;
        $this->sequenceRepo = $sequenceRpo;
        $this->tradeRepo = $tradeRepo;
        $this->transactionRepo = $transactionRepo;
        $this->signalRepo = $signalRepo;
        $this->jokerRepo = $jokerRepo;
        $this->staticWinJokerRepo = $staticWinJokerRepo;

        //récupère mon static win joker
        $this->staticWinJoker = $this->staticWinJokerRepo->findOneBy(array('id' => StaticWinJoker::DEFAULT_ID));

        //$this->montant == $parameter->getBalance();
        $this->entityManager = $em;

        $this->sequencesToExclude = array();
        $this->winOrange = false;
        // todo create api binary
        //$this->myApi = $myapi;
    }

    public function getLastParameter() {
        //parameter obj init
        return $this->parameterRepo->findOneBy(array('id' => Parameter::DEFAULT_ID));
    }

    public function updateBalance($trade) {
        // je recupère le dernière trade créer dans l'api
        $this->parameter = $this->getLastParameter();
        $this->parameter->setBalance($this->parameter->getBalance() - $trade->getAmount());
        
        $this->parameterPersister->persist($this->parameter);

        $this->entityManager->detach($this->parameter);
    }

    // Met à jours le statut du trade dans la seqeunce
    protected function setSequenceStateTradeAlgo(Sequence $sequence, Trade $trade) {
        $this->parameter = $this->getLastParameter();
        /*if ($sequence->getLength() == 1) {
            $trade->setSequenceState(Trade::SEQSTATEFIRST);
        } else if ($this->parameter->isMgActive()) {
            if ($sequence->getLength() < $this->parameter->getMartingaleSize()) {
                $trade->setSequenceState(Trade::SEQSTATEMARTING);
            } else if ($sequence->getLength() == $this->parameter->getMartingaleSize()) {
                $trade->setSequenceState(Trade::SEQSTATELASTMARTING);
            } else {
                $trade->setSequenceState(Trade::SEQSTATEUNDONE);
            }
        } else {*/
            $trade->setSequenceState(Trade::SEQSTATEUNDONE);
       // }
    }

    public function getNbSequenceOpen() {
        $listSequence = $this->sequenceRepo->findBy(array('state' => Sequence::OPEN));
        $nb = 0;
        foreach ($listSequence as $seq) {
            $nb++;
        }
        return $nb;
    }

    public function isUnderMgSize() {
        $listSequence = $this->sequenceRepo->findBy(array('state' => Sequence::OPEN));
        $this->parameter = $this->getLastParameter();
        foreach ($listSequence as $seq) {
            if ($seq->getLength() < $this->parameter->getMartingaleSize()) {
                return true;
            }
        }
        return false;
    }

    public function sizeMgSequence() {
        $listSequence = $this->sequenceRepo->findBy(array('state' => Sequence::OPEN));
        $this->parameter = $this->getLastParameter();
        foreach ($listSequence as $seq) {
            return $seq->getLength();
        }
    }

    public function checkSignalRandomTrade(TradeSignal $signal) {
        $listSgnal = Array(5, 26);
        foreach ($listSgnal as $id) {
            if ($signal->getCategorySignal()->getId() == $id) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function getModeSequence() {
        // récupre le mode de la sequence en cours
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        foreach ($listSequence as $sequence) {
            return $sequence->getMode();
        }
    }
    
    public function getSequenceOpen() {
        // récupre le mode de la sequence en cours
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        foreach ($listSequence as $sequence) {
            return $sequence;
        }
        return NULL;
    }

    public function isTradingFinish() {
//        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        
        if ($this->tradeRepo->isTrading() != NULL) {
            return false;
        } else {
            return true;
        }
    }

    // appeller toutes les secondes pour vérifier les signaux
    public function checkNewSignal($conn, $api) {
        // get the last signal in db
        $listSignals = $this->signalRepo->getLastSignals();
        $this->parameter = $this->getLastParameter();
        //check if new result
        if (!empty($listSignals)) {
            echo "non empty list signal \n";

            //New signal
            foreach ($listSignals as $signal) {

                if(($this->getModeSequence() == Sequence::EVO && $this->isTradingFinish() && $this->getNbSequenceOpen() <= UBAlgo::NB_SEQ) || $this->getNbSequenceOpen() == 0) {
                    $rate = $this->getRateSignal($signal);
                    $amount = $this->getNextMise($this->getBestRate($rate), $signal->getCategorySignal()->getId());
                    echo $amount." mise a ratrapper \n";      
                    if ($amount > 0) {

                        $trade = $this->createNewTradeForApi($signal, $amount, Trade::SEQSTATEUNDONE);
                        $sequence = $this->getSequenceForTrade($trade);
                        // Link to the sequence and set to 1 the length
                        $trade->setSequence($sequence);
                        $this->tradePersister->persist($trade);
                        // new api call with mise to send the trade
                        $this->doMise($trade, $conn, $api);
                    }
                } else if(($this->getModeSequence() == Sequence::SECURE && $this->isTradingFinish() && $this->getNbSequenceOpen() <= UBAlgo::NB_SEQ)) {
                    $rate = $this->getRateSignal($signal);
                    echo "SECURE !!!!!!!!!!!!!!!!!!!!! \n";
                    $amount = $this->getNextMiseSC($this->getBestRate($rate), $signal->getCategorySignal()->getId());
                    if ($amount > 0) {
                        $trade = $this->createNewTradeForApi($signal, $amount, Trade::SEQSTATESECURE);
                        $sequence = $this->getSequenceForTrade($trade);
                        // Link to the sequence and set to 1 the length
                        $trade->setSequence($sequence);
                        $this->tradePersister->persist($trade);
                        // new api call with mise to send the trade
                        $this->doMise($trade, $conn, $api);
                    }
                }
                else {
                    if (!$this->isTradingFinish())
                    echo "\n ----- IS TRADING FINISH FALSE \n";
                    if ($this->parameter->getIsActiveM1())
                    echo "\n ----- IS ACTIVE M1 RETOUR API 1 \n";
                }
                $signal->setIsTrade(true);
                $this->tradeSignalPersister->persist($signal);
            }
            $this->sequencesToExclude = Array();
        }
    }
    
    public function doMise(Trade $trade, $conn, $api) {
        $this->parameterPersister->persist($this->parameter);
        $this->entityManager->detach($this->parameter);
        if ($trade->getContractType() == Trade::TYPECALL) {
            $api->miseHausse($conn, $trade);
        } else {
            $api->miseBaisse($conn, $trade);
        }
    }

    public function getRateSignal(TradeSignal $signal) {
        $symbole = $signal->getSymbole();
        if ($signal->getContractType() == Trade::TYPECALL) {
            return $symbole->getLastCallRate();
        } else {
            return $symbole->getLastPutRate();
        }
    }

    public function getRateTrade(Trade $trade) {
        $symbole = $trade->getSymbole();
        if ($trade->getContractType() == Trade::TYPECALL) {
            return $this->getBestRate($symbole->getLastCallRate());
        } else {
            return $this->getBestRate($symbole->getLastPutRate());
        }
    }

    public function getBestRate($rate) {
        switch (true) {
            case ($rate >= 0.90):
                $res = 0.84;
                break;
            case ($rate >= 0.80 and $rate < 0.90):
                $res = 0.79;
                break;
            case ($rate >= 0.70 and $rate < 0.80):
                $res = 0.77;
                break;
            case ($rate < 0.70 and $rate >= 0.65):
                $res = 0.60;
                break;
            case ($rate >= 0.60 and $rate < 0.65):
                $res = 0.58;
                break;
            case ($rate < 0.60):
                $res = 0;
                break;
        }
        return $res;
    }

    public function isAlreadyTrade(TradeSignal $signal) {
        return $this->signalRepo->isSignalAlreadyTradeInSameMinute($signal);
    }
    
    public function getMiseStartToRecup() {
        return round($this->parameter->getBalance()/200,2);
    }

    public function getSequenceForTrade(Trade $trade) {
        //Retourne toutes les sequences ouvertes
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        if (empty($listSequence)) {
            echo " ------ Pas de sequence Ouverte dispo \n";
            
            $sequence = $this->sequencePersister->newSequence(1, 1, ($this->parameter->getBalance()+$trade->getAmount()));
            $this->initTrinity($sequence);
            $this->sequencePersister->persist($sequence);
            return $sequence;
        } else {
            echo " ----- Sequence Ouverte dispo \n";
            foreach ($listSequence as $sequence) {
            //if ($this->calcMiseForSequence($sequence, $this->getBestRate($trade->getRate())) == $trade->getAmount()) {
                    echo "---******- idSequence " . $sequence->getId() . ' taux : ' . $this->getRateTrade($trade) . " ----\n";
                    return $sequence;
              //  }
            }
            /*if ($this->getNbSequenceOpen() > 0){
                //
            }else*/
            return $this->sequencePersister->newSequence(1, 1,  $this->parameter->getBalance()+$trade->getAmount());
        }
    }

    public function createNewTradeForApi(TradeSignal $signal, $amount, $mode = Trade::SEQSTATEUNDONE) {

        $parameter = $this->parameterRepo->findOneBy(array('id' => Parameter::DEFAULT_ID));

        $trade = new Trade();
        if ($amount < 0.38) $amount = 0.38;
        $trade->setAmount($amount);
        $trade->setSymbole($signal->getSymbole());
        $trade->setDuration($signal->getDuration());
        $trade->setCurrency($parameter->getCurrency());
        $trade->setContractType($signal->getContractType());
        $trade->setState(Trade::STATETRADE);
        if($mode == Trade::SEQSTATESECURE){
            $trade->setSequenceState(Trade::SEQSTATESECURE);
        } else {
            $trade->setSequenceState(Trade::SEQSTATEUNDONE);
        }
        $trade->setSignalTime(new \DateTime());
        return $trade;
    }

    public function getNextMiseTR($taux = NULL) {
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        foreach ($listSequence as $sequence) {
            if($sequence->getMultiLoose() >= 3) {return UBAlgo::DEFAULT_MISE;}
            return $this->calcMise($taux, $sequence->getMise());
        }
        if ($this->getNbSequenceOpen() <= UBAlgo::NB_SEQ - 1) {
            echo "New mise Init-------------------- \n";
            return $this->getNewMiseInit();
        }
    }
    
        public function getNextMiseSC($taux = NULL, $idCategSignal) {
        //verifie si il y a une sequence ouverte
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading($idCategSignal, $this->parameter->getMartinGaleSize());

        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        foreach ($listSequence as $sequence) {
            $sequence->isFinished($this->tradeRepo);
            $this->sequencePersister->persist($sequence);
            // $this->sequencesToExclude[] = $sequence;
            return $this->calcMiseSecure($sequence, $taux);
        }
        if ($this->getNbSequenceOpen() <= UBAlgo::NB_SEQ - 1) {
            echo "New mise Init-------------------- \n";
            return $this->getNewMiseInit();
        }

        /* } */
    }
    

    public function getNextMise($taux = NULL, $idCategSignal) {
        //verifie si il y a une sequence ouverte
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading($idCategSignal, $this->parameter->getMartinGaleSize());

        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        foreach ($listSequence as $sequence) {
            $sequence->isFinished($this->tradeRepo);
            $this->sequencePersister->persist($sequence);
            // $this->sequencesToExclude[] = $sequence;
            return $this->calcMiseForSequence($sequence, $taux);
        }
        if ($this->getNbSequenceOpen() <= UBAlgo::NB_SEQ - 1) {
            echo "New mise Init-------------------- \n";
            return $this->getNewMiseInit();
        }

        /* } */
    }
    
        public function calcMiseSecure(Sequence $sequence, $taux = NULL) {

        echo"RECUP MISE SECURE  " . $sequence->getId() . "\n";
        $mise = $sequence->getMise();

        if ($mise > 0) {
            return $mise;
        } else {

            throw new \Exception('- 1 Erreur BDD Sequence ouverte avec rien a ratrapper !');
        }
    }

    public function calcMiseForSequence(Sequence $sequence, $taux = NULL) {
        $this->parameter = $this->getLastParameter();
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        echo"CALC MISE EVO  " . $sequence->getId() . "\n";
        $mise = $this->calcEvoMise($sequence, $taux);

        if ($mise > 0) {
            return $mise;
        } else {

            throw new \Exception('- 1 Erreur BDD Sequence ouverte avec rien a ratrapper !');
        }
    }

    public function setResultTrade(Trade $trade) {
        $this->parameter = $this->getLastParameter();
        if ($trade->getState() == Trade::STATEWIN && $trade->getSequenceState() != Trade::SEQSTATEDONE) {
            echo "Wintrade ! \n";
            $this->winTrade($trade);
            echo "Update balance ! \n";
            
            if($trade->getSequence()->getLength() == 1 && $trade->getAmount() == UBAlgo::DEFAULT_MISE){
                echo "TALENT 1\n";
                $this->parameter->setTalent($trade->getAmountRes());
                $this->parameterPersister->persist($this->parameter);
            } else {
                echo "TALENT 0\n";
                $this->parameter->setTalent(0);
                $this->parameterPersister->persist($this->parameter);
            }
            $this->parameter->setBalance($this->parameter->getBalance() + $trade->getAmount() + $trade->getAmountRes());
            $this->parameterPersister->persist($this->parameter);
            if (!$this->isSequenceFinish($trade->getSequence())){
                $trade->getSequence()->isFinished($this->tradeRepo);
                }
            
        } else if ($trade->getState() == Trade::STATELOOSE && $trade->getSequenceState() != Trade::SEQSTATEDONE) {
            if ($trade->getSequence()->getMode() == Sequence::SECURE) {
               $this->looseSecure($trade, $trade->getSequence()); 
            } else {
                $this->looseTrade($trade);
            }
            /*
              if($this->parameter->getBalance() < 40) {
              echo "SECURITE COMPTE";
              exit();
              }
             * 
             */
        }
    }
    
    
    public function looseSecure(Trade $trade, Sequence $sequence) {
        $trade->setState(Trade::STATELOOSE);
        $trade->setSequenceState(Trade::SEQSTATEDONE);
        $this->tradePersister->persist($trade);
        $sequence->setMultiLoose($sequence->getMultiLoose() + 1);
        $sequence->setSumLooseTR($sequence->getSumLooseTR() + $trade->getAmount());
        $this->sequencePersister->persist($sequence);
        $this->checkEndTrinitySecure($sequence, $trade);
    }

    public function isLastTR(Sequence $sequence) {
        if ($sequence->getPosition() == $sequence->getLengthTrinity()) {
            echo "LAST LOOP --------------\n";
            $sequence->setPosition(0);
            $sequence->setSumWinTR(0);
            $sequence->setMise(round($sequence->getSumLooseTR() / $sequence->getLengthTrinity(), 2));
            if ($sequence->getMise() < 0.4) {
                $sequence->setMise(0.45);
            }
            $this->sequencePersister->persist($sequence);
        }
    }

    public function winTrade(Trade $trade) {
        $sequence = $trade->getSequence();
        /*if ($this->isSequenceFinish($sequence, ($trade->getAmountRes() - $trade->getAmount()))) {
            return true;
        }*/
        if ($sequence->getMode() == Sequence::EVO) {
            echo "WIN EVO\n";
            $this->winTp($trade, $sequence);
        } else {
            echo "WIN SECURE\n";
            $this->winSecure($trade, $sequence);
        }
        // je met à jours le statut du trade gagnant
        $trade->win();
        $this->tradePersister->persist($trade);
    }

    public function winTR(Trade $trade, Sequence $sequence) {
        
        $sequence->setSumWinTR($this->calcSumCatchUp($trade, $sequence));
        $sumWinTR = $sequence->getSumWinTR();
        $sequence->setSumLooseTR($sequence->getSumLooseTR() - $sumWinTR);
        $this->sequencePersister->persist($sequence);
        $trade->setSequenceState(Trade::SEQSTATEDONE);
        $this->tradePersister->persist($trade);

        
       // $this->modeRouge($sequence);
      
    }
    
    public function isSequenceFinish(Sequence $sequence)
    {
        if ($sequence->getBalanceStart() < $this->parameter->getBalance())
        {
            $sequence->setState(Sequence::CLOSE);
            $sequence->setTimeEnd(new \DateTime());
            $this->sequencePersister->persist($sequence);
            return true;
        }
        return false;
    }


    public function modeOrange(Sequence $sequence) {
        if($sequence->getMultiWin() == 2 ) {// orange actif tous le temps ancienne condtion : $sequence->getMise() >= ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)
            echo "\n ---- MODE ORANGE ----  \n";
            $this->initTrinity($sequence);
            $this->sequencePersister->persist($sequence);
            $this->winOrange = true;
            return true;
        }
         echo "\n ---- PAS ORANGE ----  \n";
        return false;
    }
    
    public function isModeOrange(Sequence $sequence = NULL) {
        if ($sequence != NULL &&$sequence->getMode() == Sequence::TRINITY){
         if($sequence != NULL && $sequence->getSumLooseTR() >= ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)) {
             echo "MODE_ORANGE ----- TRINITY  \n";
             return true;
         }
        } else {
          if($sequence != NULL && $sequence->getSumLoose() >= ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)) {
             echo "MODE_ORANGE -----SUM LOOSE => ".$sequence->getSumLoose()."  MONTANT ORANGE => ".($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)." \n";
             return true;
         }  
        }
         return false;
    }
    public function modeRouge(Sequence $sequence) {
        if($sequence->getMultiWin() == 1 && $sequence->getPosition() == 1 && $sequence->getMise() >= ($this->parameter->getBalance() * UBAlgo::MODE_ROUGE)) {
            echo "\n ---- MODE ROUGE ----  \n";
            $this->initTrinity($sequence);
            $this->sequencePersister->persist($sequence);
            $this->winOrange = false;
            return true;
        }
        echo "\n ---- PAS ROUGE ----  \n";
        return false;
    }

    public function initTrinity(Sequence $sequence) {
        $sequence->setPosition(0);
       // $sequence->setSumLooseTR($sequence->getSumLooseTR() - $sequence->getSumWinTR());
        $sequence->setSumWinTR(0);
        $sequence->setMise(round($sequence->getSumLooseTR() / $sequence->getLengthTrinity(), 2));
        if ($sequence->getMise() < 0.4) {
            $sequence->setMise(0.45);
        }
        $this->sequencePersister->persist($sequence);
    }

    public function calcSumCatchUp(Trade $trade, Sequence $sequence) {

        $sumToRecup = $this->floorDec($trade->getAmountRes() - ($sequence->getMise() / 0.98));
        if ($sumToRecup > 0) {
            $sequence->setSumToRecup($sequence->getSumToRecup() + $sumToRecup);
        }
        $this->sequencePersister->persist($sequence);
        return $trade->getAmountRes() - $sumToRecup;
    }

    public function floorDec($nb) {
        return floor($nb * 100) / 100;
    }
    
    public function winTp(Trade $trade, Sequence $sequence) {
         if ($sequence->getLength() > 1 && $trade->getAmountRes() >= $sequence->getNextAmountSequence($this->tradeRepo)) {
            $this->updateLastUndoneTrade($sequence);
            
           // ($sequence->getMultiLoose() > 0)? $sequence->setMultiLoose($sequence->getMultiLoose() - 1): false;
            //$this->sequencePersister->persist($sequence);
        } else { // rien a rattraper dans cette sequence
            echo "Rien a rattraper sequence Close\n";
        }
    }
    
    public function updateLastUndoneTrade(Sequence $sequence){
        echo "Maj Trade non ratrape dans sequence  \n";
            $undoneTrade = $sequence->getNextUndoneTrade($this->tradeRepo);
            $undoneTrade->setSequenceState(Trade::SEQSTATEDONE);
            $this->tradePersister->persist($undoneTrade);
    }
    
    public function isStillSecureMode(Sequence $sequence){
        echo "IS STILL SECURE \n";
            $undoneTrade = $this->tradeRepo->getUndoneTradeOrNull($sequence);
            if ($undoneTrade != NULL) { // toujours en mode SECURE
                echo "YES \n";
                return $undoneTrade->getAmount();
            } else // plus en mode secure je rebascule en EVO
            {
                echo "NO \n";
                $sequence->setMode(Sequence::EVO);
                $this->sequencePersister->persist($sequence);
                return 0;
            }
    }
    
    public function winSecure(Trade $trade, Sequence $sequence) {
    //incrementer sumwin        
    // si somme gagner moins somme perdu sur ce bloque superieur à somme a recup cloture
        $sequence->setMultiWin($sequence->getMultiWin() + 1);
        $sequence->setSumWinTR($sequence->getSumWinTR() + $trade->getAmountRes());
        $this->sequencePersister->persist($sequence);
        if (($sequence->getSumWinTR() - $sequence->getSumLooseTR()) >= $sequence->getSumToRecup()) {
            echo "fin du mode Secure pour 1 treau \n";
            $this->updateLastUndoneTrade($sequence);
            echo "fin du mode Secure pour 2 treau \n";
            $amount = $this->isStillSecureMode($sequence);
            echo "fin du mode Secure pour 3 treau \n";
            $this->initSecureMode($sequence, $amount);
            echo "fin du mode Secure pour 4 treau \n";
            $this->sequencePersister->persist($sequence);
            echo "fin du mode Secure pour 5 treau \n";
        } else {
                    $this->checkEndTrinitySecure($sequence, $trade);//check fin trinity Secure
        }
 
    }
    
    public function checkEndTrinitySecure(Sequence $sequence, Trade $trade) {
        $length = $sequence->getMultiLoose() + $sequence->getMultiWin();
        $sumLoose = $sequence->getSumLooseTR() - $sequence->getSumWinTR();
        if ($length == 10) {
            if ($sequence->getMultiLoose() == 5 && $sequence->getMultiWin() == 5) {
                $newTrade = $this->tradePersister->newTradeIntercale($sumLoose, $trade, TRUE);
                $this->looseTrade($newTrade);
                $amount = $this->isStillSecureMode($sequence);
                $this->initSecureMode($sequence, $amount);
            }
            if ($sequence->getMultiLoose() == 6 && $sequence->getMultiWin() == 4) {
                // créer trade loose undone
                $newTrade = $this->tradePersister->newTradeIntercale($sumLoose, $trade);
                $this->looseTrade($newTrade);
                $amount = $this->isStillSecureMode($sequence);
                $this->initSecureMode($sequence, $amount);
            }
        }
        if ($length == 20) {
            // créer 2 trade loose undone
            $amount = round($sumLoose / 2, 2);
            $newTrade = $this->tradePersister->newTradeIntercale($amount, $trade); // 1
            $this->looseTrade($newTrade);
            $newTrade2 = $this->tradePersister->newTradeIntercale($amount, $trade); // 2
            $this->looseTrade($newTrade2);
            $tradeAmount = $this->isStillSecureMode($sequence);
            echo "INIT secure \n";
            $this->initSecureMode($sequence, $tradeAmount);
        }
    }

    public function winMG(Trade $trade, Sequence $sequence) {

        if ($this->parameter->isMgActive()) {
            echo "Maj Trade non ratrape MG ----- dans sequence \n";
            $this->martinGWin($trade, $sequence);
        } else if ($sequence->getLength() > 1 && $trade->getAmountRes() >= $sequence->getNextAmountSequence($this->tradeRepo)) {
            echo "Maj Trade non ratrape dans sequence \n";
            $undoneTrade = $sequence->getNextUndoneTrade($this->tradeRepo);
            $undoneTrade->setSequenceState(Trade::SEQSTATEDONE);
            $this->tradePersister->persist($undoneTrade);
        } else { // rien a rattraper dans cette sequence
            echo "Rien a rattraper sequence Close\n";
            // vérifier si aucune autre sequence ouverte pour joker
            //$this->checkJoker();
        }
    }

    public function martinGWin(Trade $trade, Sequence $sequence) {
        // récupérer sum win total
        $res = $this->tradeRepo->getSumWinSequence($sequence);
        // recupérer tous les loose
        $trades = $this->tradeRepo->getLooseTrades($sequence);

        foreach ($trades as $tradeMg) {
            // echo $tradeMg->getSequenceState();
            // echo $res. " <- Res - tradeAmount : ". $tradeMg->getAmount()." -- \n"; 
            if ($tradeMg->getState() == Trade::STATELOOSE && $tradeMg->getAmount() < $res) {
                echo "Sequence done " . $tradeMg->getId() . "\n";
                $tradeMg->setSequenceState(Trade::SEQSTATEDONE);
                $this->tradePersister->persist($tradeMg);
                $res -= $tradeMg->getAmount();
            } else { // dans ce cas sequence non terminée
                /* $sequence->setState(Sequence::CLOSE);
                  $this->sequencePersister->persist($sequence);
                  $newSequence = new Sequence();
                  $newAmount = $tradeMg->getAmount() - $res;
                  $tradeMg->setAmount($res);
                  // créer nouveau trade dans la sequence avec amount 2
                  $newTrade = $this->tradePersister->newTradeIntercale($newAmount, $tradeMg);
                  $newSequence->addTrade($newTrade);
                  $this->sequencePersister->persist($newSequence);
                  return; */
            }
        }

        $this->sequencePersister->persist($sequence);
    }

    public function checkJoker() {
        if ($this->parameter->getProbaJokerOn()) {
            if ($this->parameter->getJokerConsecutive()) {
                if (!$this->sequenceRepo->isSequenceOpen()) {
                    $this->setJokerData();
                }
            } else {
                $this->setJokerData();
            }
        }
    }

    public function setJokerData() {
        //incrémenter win consecutif
        $this->staticWinJoker->addWin();
        // si win consecutif = palier joker 
        if ($this->staticWinJoker->getConsecutiveWin() == $this->parameter->getJokerSize()) {
            // reset win consecutif et incrementer joker
            $this->jokerPersister->newJokerUnUse();
            $this->staticWinJoker->resetWin();
        }
    }

    public function looseTrade(Trade $trade) {
        echo "LOOOOSE\n";
        $trade->setState(Trade::STATELOOSE);
        $sequence = $trade->getSequence();
        $this->looseTalent($trade, $trade->getSequence());
        $this->tradePersister->persist($trade);
        $nbLoose = $sequence->getNbLooseEvo($this->tradeRepo);
        if ($nbLoose > 3 && $nbLoose < 7) {
            if ($nbLoose % 3 == 1) {
                $this->looseMod4($trade, $sequence, $nbLoose);
            }
            if ($nbLoose % 3 == 2) {
                $this->looseMod5($trade, $sequence, $nbLoose);
            }
            if ($nbLoose % 3 == 0) {
                $this->looseMod6($trade, $sequence, $nbLoose);
            }
        } else if ($nbLoose > 6) {
            if ($nbLoose % 2 == 1) {
                $this->looseMod7($trade, $sequence, $nbLoose);
            }
            if ($nbLoose % 2 == 0) {
                $this->looseMod8($trade, $sequence, $nbLoose);
            }
        }
        $this->looseTalent($trade, $trade->getSequence());
        if ($sequence->getMode() == Sequence::EVO) {
            $this->checkSecureMode($sequence, $trade);  //appele la fonction qui initialialise la sequence en secure si necessaire
        }
    }
    
    public function checkSecureMode(Sequence $sequence, Trade $trade){
        // initialise la trnity secure
        if($sequence->getSumToRecup() == 0) {
            $this->initSecureMode($sequence, $trade->getAmount());
        }
        // si mise pas ratrappé je ne fais rien pour continuer a recuperer ma somme perdu
    }
    
    public function initSecureMode(Sequence $sequence, $amount) {
        if($amount > 0){
            $sequence->setMode(Sequence::SECURE);
        }
        $sequence->setSumToRecup($amount);
        $sequence->setMultiLoose(0);
        $sequence->setMultiWin(0);
        $sequence->setSumLooseTR(0);
        $sequence->setSumWinTR(0);
        if ($amount < 0.68 ){
            $sequence->setMise(round(($amount/1.4),2));
        }
        else {
        $sequence->setMise(round(($amount/1.5),2));    
        }
        $this->sequencePersister->persist($sequence);
    }
    
    
    public function looseTalent(Trade $trade, Sequence $sequence){
        if ($sequence->getLength() == 1 && $trade->getAmount() > UBAlgo::DEFAULT_MISE){
            $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() + 0.46 ); //TODO mise defaut x taux devise
            $this->parameterPersister->persist($this->parameter);
            $this->sequencePersister->persist($sequence);
            $trade->setAmount(UBAlgo::DEFAULT_MISE);
            $this->tradePersister->persist($trade);
        }
    }

    public function looseMod4(Trade $trade, Sequence $sequence, $nbLoose) {
        $halfAmount = round($trade->getAmount() / 2, 2);
        $trade->setAmount($halfAmount);
        $this->tradePersister->persist($trade);
        $sumLooseTalent = 0;
        if ($this->parameter->getSumLooseTalent() > 0 && $this->parameter->getSumLooseTalent() > UBAlgo::DEFAULT_MISE) {
            $sumLooseTalent = 0.46;
            $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() - 0.46);// TODO
            $this->parameterPersister->persist($this->parameter);
        } else {
            $this->parameter->getSumLooseTalent(); //talent
            $this->parameter->setSumLooseTalent(0);
            $this->parameterPersister->persist($this->parameter);
        }
        $sumToRepart = round($halfAmount / $nbLoose, 2);
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart + $sumLooseTalent);
    }

    public function looseMod5(Trade $trade, Sequence $sequence, $nbLoose) {
        $this->sequencePersister->persist($sequence);
        $halfAmount = round($trade->getAmount()/2, 2);
        $this->checkHalfAmount($sequence, $trade, $halfAmount);
        $sumToRepart = round($halfAmount/ $nbLoose, 2);// -1 pour retirer la première mise a ratrapper
        echo "DEMI SUMTOREPART : $sumToRepart nbtradeLoose : ".$nbLoose. '\n';
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart);
    }
    
    public function checkHalfAmount(Sequence $sequence, Trade $trade, $halfAmount) {
        if ($sequence->checkHalfTradeAndFull($this->tradeRepo, $this->tradePersister,  $halfAmount)){
            $trade->setSequenceState(Trade::SEQSTATEDONE);
            $this->tradePersister->persist($trade);
        } else {
            $trade->setAmount($halfAmount);
            $trade->setSequenceState(Trade::SEQSTATEHALF);
            $this->tradePersister->persist($trade);
        }
    }
    
    public function looseMod6(Trade $trade, Sequence $sequence, $nbLoose) {
        $trade->setSequenceState(Trade::SEQSTATEDONE);
        $this->tradePersister->persist($trade);
        echo "FULL SUMTOREPART : ".$trade->getAmount()." nbtradeLoose : ".($nbLoose - 1). '\n';
        $sumToRepart = round($trade->getAmount() / ($nbLoose - 1), 2);
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart);
    }
    
        public function looseMod7(Trade $trade, Sequence $sequence, $nbLoose) {
        $this->sequencePersister->persist($sequence);
        $halfAmount = round($trade->getAmount()/2, 2);
        $sumToRepart = round($halfAmount/ $nbLoose, 2);// -1 pour retirer la première mise a ratrapper
        echo "DEMI SUMTOREPART : $sumToRepart nbtradeLoose : ".$nb. '\n';
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart);
    }
    
        public function looseMod8(Trade $trade, Sequence $sequence, $nbLoose) {
        $this->looseMod5($trade, $sequence, $nbLoose);
        $sumToRepart = $sequence->eraseSmallTrade();
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart);
        
    }
    
    
    //initialise les statut de trade à Done au passage en mode trinity pour ne pas avoir de conflit plus tard
    public function resetTradeStateForTrinity(Sequence $sequence) {
         $trades = $this->tradeRepo->getTradeForSequence($sequence);
            foreach ($trades as $trade) {
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                    $trade->setSequenceState(Trade::SEQSTATEDONE);
                    $this->tradePersister->persist($trade);
                }
            }
    }

    
    
    public function getNewMiseInit() {

        $balance = ($this->parameter->getBalance() - 400000);

        if($this->parameter->getTalent()) {
            echo "compte : $balance \n";
            $talent = $this->parameter->getTalent();
            $this->parameter->setTalent(0);
            $this->parameterPersister->persist($this->parameter);
                return UBAlgo::DEFAULT_MISE + $talent ;
            //return round(($balance * 0.001) /2, 2);
            //return UBAlgo::DEFAULT_MISE;
            //return $this->calcMise(NULL, $this->getMiseStartToRecup()/3);
        } else {
             return UBAlgo::DEFAULT_MISE;
            //return $this->calcMise(NULL, $this->getMiseStartToRecup()/3);
        }
    }

    public function calcMise($taux, $amount) {
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        return round(($amount / ($taux)) + 0.01, 2);
    }

    public function calcMgMise(Sequence $sequence, $taux) {
        if ($sequence->getLength() < $this->parameter->getMartingaleSize()) {
            echo "debug calc nextMg id " . $sequence->getId() . "\n";
            $trades = $this->tradeRepo->getTradeForSequence($sequence);
            $amount = 0;
            foreach ($trades as $trade) {
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                    $amount += $trade->getAmount();
                    echo "recupère la valeure a recup " . $amount . "\n";
                }
            }
        } else if ($sequence->getLength() == ($this->parameter->getMartingaleSize())) { // j'ai atteint le max de MG
            echo "Max MG \n";
            return $sequence->getLastMgSequence($this->tradeRepo);
        } else {
            echo "sequence apres MG \n";
            $amount = $sequence->getNextAmountSequenceMg($this->tradeRepo);
        }
        echo round(($amount / ($taux)) + 0.01, 2) . " ******  test taux " . $taux . " \n";
        return round(($amount / ($taux)) + 0.01, 2);
    }
    
    public function calcEvoMise(Sequence $sequence, $taux) {

            echo "debug calc Evo id " . $sequence->getId() . "\n";
            $trades = $this->tradeRepo->getTradeForSequence($sequence);
            echo "DEBUG 1\n";
            $amount = 0;
            
            foreach ($trades as $trade) {
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN ) {
                    $amount += $trade->getAmount();
                    echo "recupère la valeure a recup " . $amount . "\n";
                    break;
                }
            }
echo "DEBUG 2\n";            
// SECURITE si mise supérieur a 10 euro
            $taux =  ($amount > 2)? 0.92 : $taux; // TODO transformer 10 en une variable avec un ration par rapport au compte
            // Securite last trade too big
            $nbLoose = $sequence->getNbLooseEvo($this->tradeRepo) + 1;
            if ($nbLoose == 1 && $sequence->getLength() > 20) {
                $amountTmp = $sequence->getBalanceStart() - $this->parameter->getBalance() + ($sequence->getLength() * 0.035);
                $amount = ($amountTmp < $amount)? $amountTmp : $amount;
            }
            echo "DEBUG 3\n";
            if (round(($amount / ($taux)) + 0.01, 2)   < 0.5 && $amount > 0){
                return 0.5;
            }
echo "DEBUG 4\n";
        echo round(($amount / ($taux)) + 0.01, 2) . " ******  test taux " . $taux . " \n";
        return round(($amount / ($taux)) + 0.01, 2);
    }
    // initialise tous les signaux non pris a 1 pour eviter les mauvais signaux
    public function initTradeSignalBegin() {
        $listSignal = $this->signalRepo->findByIsTrade(0);
        foreach ($listSignal as $signal) {
            $signal->setIsTrade(1);
            $this->tradeSignalPersister->persist($signal);
        }
    }

    public function getSumToRecup(Sequence $sequence) {
        // en trinty je recupère ma valeur dans sequence
        if($sequence->getMode() == Sequence::TRINITY) {
            echo "SUM TO RECUP ". $sequence->getSumLooseTR()."\n";
            return $sequence->getSumLooseTR();
        }
        //sinon je calcule la somme perdue par rapport au trades
        $trades = $this->tradeRepo->getTradeForSequence($sequence);
        $amount = 0;
        foreach ($trades as $trade) {
            if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                $amount += $trade->getAmount();
            }
        }
        echo "SUM TO RECUP". $amount."\n";
        return $amount;
    }

}
