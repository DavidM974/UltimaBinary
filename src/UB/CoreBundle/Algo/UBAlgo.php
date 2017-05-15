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
    private $proba;
    private $nbSequence;
    private $parameter;
    private $miseInitial;
    private $miseCourante;
    private $staticWinJoker;
    
    const DEFAULT_MISE = 1;

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
    
    
    public function __construct(ParameterPersister $parameterPersister, TradePersister $tradePersister, SequencePersister $sequencePersister, JokerPersister $jokerPersister,
                                StaticWinJokerPersister $staticWinJokerPersister, TradeSignalPersister $tradeSignalPersister, ParameterRepository $parameterRepo, 
                                TradeRepository $tradeRepo, TradeSignalRepository $signalRepo, TransactionRepository $transactionRepo, 
                                SequenceRepository $sequenceRpo, JokerRepository $jokerRepo, StaticWinJokerRepository $staticWinJokerRepo, $proba, $nbSequence) {
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
        $this->staticWinJoker = $this->staticWinJokerRepo->findOneBy(array('id' => StaticWinJoker::DEFAULT_ID)) ;

        //$this->montant == $parameter->getBalance();
        $this->proba = $proba;
        $this->nbSequence = $nbSequence;

        // todo create api binary
        //$this->myApi = $myapi;
    }
    
    public function getLastParameter() {
        //parameter obj init
        return $this->parameterRepo->findOneBy(array('id' => Parameter::DEFAULT_ID));
    }

    public function newTradeFromApi($idTrade) {
        // je recupère le dernière trade créer dans l'api
        $trade = $this->tradeRepo->findOneBy(array('id' => $idTrade));
        $this->parameter = $this->getLastParameter();
        $this->parameter->setBalance($this->parameter->getBalance() - $trade->getAmount());
        $this->parameterPersister->persist($this->parameter);
        //get the sequence where this trade should be link and save in db
        $sequence = $this->getSequenceForTrade($trade);
        // Link to the sequence and set to 1 the length
        $trade->setSequence($sequence);
        // Mise à jours de l'etat du trade Martin gale ou pas 
        $this->setSequenceStateTradeAlgo($sequence, $trade);      
        
        $this->tradePersister->persist($trade);
    }
    // Met à jours le statut du trade dans la seqeunce
    protected function setSequenceStateTradeAlgo(Sequence $sequence, Trade $trade) {
        $this->parameter = $this->getLastParameter();
        if ($sequence->getLength() == 1) {
            $trade->setSequenceState(Trade::SEQSTATEFIRST);
        } else if ($this->parameter->isMgActive()) {
            if ($sequence->getLength() < $this->parameter->getMartingaleSize()) {
                $trade->setSequenceState(Trade::SEQSTATEMARTING);
            } else if ($sequence->getLength() == $this->parameter->getMartingaleSize()) {
                $trade->setSequenceState(Trade::SEQSTATELASTMARTING);
            } else {
                $trade->setSequenceState(Trade::SEQSTATEUNDONE);
            }
        } else {
            $trade->setSequenceState(Trade::SEQSTATEUNDONE);
        }
    }


    // appeller toutes les secondes pour vérifier les signaux
    public function checkNewSignal($conn, $api) {
        // get the last signal in db
        $listSignals = $this->signalRepo->getLastSignals();

        //check if new result
        if (!empty($listSignals)) {
            echo "non empty list signal \n";
            //New signal
            foreach ($listSignals as $signal) {
                echo "signal" . $signal->getId() . " \n";
                // récupère la prochaine valeur de mise
                if (!$this->isAlreadyTrade($signal)) {
                    $amount = $this->getNextMise();
                    $trade = $this->createNewTradeForApi($signal, $amount);

                    // new api call with mise to send the trade
                    if ($trade->getContractType() == Trade::TYPECALL) {
                        $api->miseHausse($conn, $trade);
                    } else {
                        $api->miseBaisse($conn, $trade);
                    }
                } else
                    echo "Alreay trade identique \n";
                $signal->setIsTrade(true);
                $this->tradeSignalPersister->persist($signal);
            }
        }
    }

    public function isAlreadyTrade(TradeSignal $signal) {
        return $this->signalRepo->isSignalAlreadyTradeInSameMinute($signal);
    }
    

    public function getSequenceForTrade(Trade $trade) {
        //Retourne toutes les sequences ouvertes
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        if (empty($listSequence)) {
            echo " ------ Pas de sequence Ouverte dispo \n";
            $sequence = $this->sequencePersister->newSequence();
            $this->sequencePersister->persist($sequence);
            return $sequence;
        } else {
            echo " ----- Sequence Ouverte dispo \n";
            foreach ($listSequence as $sequence) {
                if ($this->calcMiseForSequence($sequence, $this->parameter->getDefaultRate()) == $trade->getAmount()) {
                    echo "Montant à ratrapper : ". $sequence->getNextAmountSequence($this->tradeRepo).' taux : '.$this->parameter->getDefaultRate()."\n";
                    echo 'mise calculer : ' . $this->calcMise($this->parameter->getDefaultRate(), $sequence->getNextAmountSequence($this->tradeRepo)) . ' mise trade : ' . $trade->getAmount() . "\n";
                    return $sequence;
                }
            }
            return $this->sequencePersister->newSequence();
        }
    }

    public function createNewTradeForApi(TradeSignal $signal, $amount) {
        
        $parameter = $this->parameterRepo->findOneBy(array('id' => Parameter::DEFAULT_ID));
        
        $trade = new Trade();
        $trade->setAmount($amount);
        $trade->setSymbole($signal->getSymbole());
        $trade->setDuration($signal->getDuration());
        $trade->setCurrency($parameter->getCurrency());
        $trade->setContractType($signal->getContractType());
        $trade->setSignalTime(new \DateTime());
        return $trade;
    }
    

    public function getNextMise($taux = NULL) {
        //verifie si il y a une sequence ouverte
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        if (empty($listSequence)) {
            return $this->getNewMiseInit();
        } else {
            echo "Sequence non terminee \n";
            if ($taux == NULL) {
                $taux = $this->parameter->getDefaultRate();
            }
            foreach ($listSequence as $sequence) {
               return $this->calcMiseForSequence($sequence, $taux);
            }
        }
    }
    
    public function calcMiseForSequence(Sequence $sequence, $taux = NULL) {
        $this->parameter = $this->getLastParameter();
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        if ($this->parameter->isMgActive()) {
            echo"MG Active \n";
            $mise = $this->calcMgMise($sequence, $taux);
        } else {
            echo"MG Not Active ".$sequence->getId()."\n";
            $amount = $sequence->getNextAmountSequence($this->tradeRepo);
            $mise = $this->calcMise($taux, $amount);
        }
        if ($mise > 0) {
            return $mise;
        } else {
          
            throw new \Exception('- 1 Erreur BDD Sequence ouverte avec rien a ratrapper !');
            
        }
    }


    public function setResultTrade(Trade $trade)
    {
        $this->parameter = $this->getLastParameter();
        if ($trade->getState() == Trade::STATEWIN && $trade->getSequenceState() != Trade::SEQSTATEDONE) {
            echo "Wintrade ! \n";
            $this->winTrade($trade);
             echo "Update balance ! \n";
            $this->parameter->setBalance($this->parameter->getBalance()+$trade->getAmount()+$trade->getAmountRes());
            $this->parameterPersister->persist($this->parameter);
            
        } else if ($trade->getState() == Trade::STATELOOSE && $trade->getSequenceState() != Trade::SEQSTATEDONE){
            $this->looseTrade($trade);
        } 
    }

    public function winTrade(Trade $trade) {
        $sequence = $trade->getSequence();
        // voir cette partie pour les benefices
        if ($sequence->getSumWin() > $sequence->getSumLoose()) {
            echo "Sum win > sum loose Close Seq\n";
            $sequence->initTradeWin($this->tradeRepo);
            $sequence->isFinished();
            $this->sequencePersister->persist($sequence);
            if ($sequence->getLength() ==  1) {
                $this->checkJoker();
            }
            return;
        }
        
        if($this->parameter->isMgActive()) {
             echo "Maj Trade non ratrape MG ----- dans sequence \n";
            $this->martinGWin($trade, $sequence);
        }
        else if ($sequence->getLength()> 1 &&  $trade->getAmountRes() >= $sequence->getNextAmountSequence($this->tradeRepo)) {
            echo "Maj Trade non ratrape dans sequence \n";
            $undoneTrade = $sequence->getNextUndoneTrade($this->tradeRepo);
            $undoneTrade->setSequenceState(Trade::SEQSTATEDONE);
            $this->tradePersister->persist($undoneTrade);
        } else { // rien a rattraper dans cette sequence
            echo "Rien a rattraper sequence Close\n";
            // vérifier si aucune autre sequence ouverte pour joker
           //$this->checkJoker();
        }
        
        //todo trade intercale
        
        

        // je vérifie si la sequence et cloturé pour mettre à jour la sequence
        $sequence->isFinished();
        $this->sequencePersister->persist($sequence);
        // je met à jours le statut du trade gagnant
        $trade->win();
        $this->tradePersister->persist($trade);  
        }

    public function martinGWin(Trade $trade, Sequence $sequence) {
        $res = $trade->getAmountRes();
        $trades =  $this->tradeRepo->findBySequence($sequence);
        foreach ($trades as $tradeMg) {
            echo $res. " <- Res - tradeAmour : ". $tradeMg->getAmount()." -- \n"; 
            if ($tradeMg->getState() == Trade::STATELOOSE && $tradeMg->getAmount() < $res) {
                $tradeMg->setSequenceState(Trade::SEQSTATEDONE);
                $this->tradePersister->persist($tradeMg);
                $res -= $tradeMg->getAmount();
            } else { // dans ce cas sequence non terminée
                /* todo créer un signal intercale non un trade
                $newAmount = $tradeMg->getAmount() - $res;
                $tradeMg->setAmount($res);
                // créer nouveau trade dans la sequence avec amount 2
                $newTrade = $this->tradePersister->newTradeIntercale($newAmount, $tradeMg);
                $sequence->addTrade($newTrade);
                $this->sequencePersister->persist($sequence);
                 * 
                 */
                return;
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
        echo "-------loose---------\n";
        $trade->setState(Trade::STATELOOSE);
        $sequence = $trade->getSequence();
        // set consecutive win 0
        if ($this->parameter->getJokerConsecutive()) {
            $this->staticWinJoker->resetWin();
            $this->staticWinJokerPersister->persist($this->staticWinJoker);
        }
        /*
        $joker = $this->jokerRepo->findOneBy(array('state' => Joker::STATEUNUSE));
        if (!empty($joker) AND $sequence->getLength() == 1) {
            echo "----------+++JOKER+++---------\n";
            //init sequence
            $sequence->setJoker(Sequence::JOKERUSE);
            $sequence->setState(Sequence::CLOSE);
            $this->sequencePersister->persist($sequence);
            //init joker
            $joker->setState(Joker::STATEUSE);
            $this->jokerPersister->persist($joker);
            $trade->setSequenceState(Trade::SEQSTATEDONE);
        }  */
        $this->tradePersister->persist($trade);
    }
    public function getNewMiseInit() {

        $balance = $this->parameter->getBalance();

        if ($balance >= 1000) {
            echo "compte : $balance \n";
            return round($balance / 1000, 2);
        } else {
            return UBAlgo::DEFAULT_MISE;
        }
    }

    public function calcMise($taux, $amount) {
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        return ceil($amount / ($taux));
    }

    public function calcMgMise(Sequence $sequence, $taux) {
        if ($sequence->getLength() < $this->parameter->getMartingaleSize()) {
            echo "debug calc nextMg id ".$sequence->getId()."\n";
            $trades = $this->tradeRepo->getTradeForSequence($sequence);
            $amount = 0;
            foreach ($trades as $trade) {
                echo "id trade :".$trade->getId()."\n";
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                    $amount += $trade->getAmount();
                    echo "recupère la valeure a recup ". $amount ."\n";
                }
            }
        } else if ($sequence->getLength() == ($this->parameter->getMartingaleSize())) { // j'ai atteint le max de MG
            echo "Max MG \n";
            return $sequence->getLastMgSequence($this->tradeRepo);
        } else {
            echo "sequence apres MG \n";
            $amount = $sequence->getNextAmountSequenceMg($this->tradeRepo);
        }
        echo ceil($amount / ($taux)). "  test \n";
        return ceil($amount / ($taux));
    }

}
