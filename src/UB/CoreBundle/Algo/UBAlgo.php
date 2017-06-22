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

    const DEFAULT_MISE = 0.5;
    const NB_SEQ = 1;

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
        $this->parameter->setIsActiveM1(false);
        $this->parameterPersister->persist($this->parameter);
        //get the sequence where this trade should be link and save in db
        $sequence = $this->getSequenceForTrade($trade);
        $sequence->setPosition($sequence->getPosition() + 1);
        // Link to the sequence and set to 1 the length
        $trade->setSequence($sequence);
        // Mise à jours de l'etat du trade Martin gale ou pas 
        $this->setSequenceStateTradeAlgo($sequence, $trade);

        $this->tradePersister->persist($trade);
        $this->entityManager->detach($this->parameter);
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

    public function isTradingFinish() {
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        if (empty($listSequence)) {
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

                // récupère la prochaine valeur de mise
               /*
                 if (($this->isUnderMgSize() && $this->getModeSequence() == Sequence::MG && $this->isTradingFinish() && !$this->parameter->getIsActiveM1())) {
                    $rate = $this->getRateSignal($signal);
                    echo "MODE MARTIN G !!!!!!!!!!!!!!!!!!!!! \n";
                    $amount = $this->getNextMise($this->getBestRate($rate), $signal->getCategorySignal()->getId());
                    if ($amount > 0) {

                        $sizeMg = $this->sizeMgSequence();
                        echo "MG NUMBER : " . $sizeMg . "\n";
                        $trade = $this->createNewTradeForApi($signal, $amount);
                        // new api call with mise to send the trade
                        $this->parameter->setIsActiveM1(true);
                        $this->parameterPersister->persist($this->parameter);
                        $this->entityManager->detach($this->parameter);
                        if ($trade->getContractType() == Trade::TYPECALL) {
                            $api->miseHausse($conn, $trade);
                        } else {
                            $api->miseBaisse($conn, $trade);
                        }
                    }
                } // permet  de prendes les signaux en dehors de la martin G avec les signaux pour cloturer la sequence
                else*/ 
                if (($this->getModeSequence() == Sequence::TRINITY && $this->isTradingFinish() && !$this->parameter->getIsActiveM1() && $this->getNbSequenceOpen() <= UBAlgo::NB_SEQ) || ($this->getNbSequenceOpen() == 0)) {
                    $rate = $this->getRateSignal($signal);
                    echo "MODE INFINITY !!!!!!!!!!!!!!!!!!!!! \n";
                    $amount = $this->getNextMiseTR($this->getBestRate($rate));
                    if ($amount > 0) {
                        $trade = $this->createNewTradeForApi($signal, $amount);
                        // new api call with mise to send the trade
                        //$this->parameter->setIsActiveM1(true);
                        $this->parameterPersister->persist($this->parameter);
                        $this->entityManager->detach($this->parameter);
                        if ($trade->getContractType() == Trade::TYPECALL) {
                            $api->miseHausse($conn, $trade);
                        } else {
                            $api->miseBaisse($conn, $trade);
                        }
                    }
                } else {
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
            case ($rate >= 0.86):
                $res = 0.77;
                break;
            case ($rate >= 0.80 and $rate < 0.86):
                $res = 0.75;
                break;
            case ($rate >= 0.70 and $rate < 0.80):
                $res = 0.65;
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
            $sequence = $this->sequencePersister->newSequence($this->parameter->getInfinitySize(), $this->getMiseStartToRecup());
            $this->initTrinity($sequence);
            $this->sequencePersister->persist($sequence);
            return $sequence;
        } else {
            echo " ----- Sequence Ouverte dispo \n";
            foreach ($listSequence as $sequence) {
                if ($sequence->getMode() == Sequence::TRINITY && $trade->getAmount() >= $sequence->getMise()) {
                    return $sequence;
                }
                else if ($this->calcMiseForSequence($sequence, $this->getBestRate($trade->getRate())) == $trade->getAmount()) {
                    echo "---******- idSequence " . $sequence->getId() . ' taux : ' . $this->getRateTrade($trade) . " ----\n";
                    return $sequence;
                }
            }
            return $this->sequencePersister->newSequence($this->parameter->getInfinitySize(), $this->getMiseStartToRecup());
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

    public function getNextMiseTR($taux = NULL) {
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading();
        foreach ($listSequence as $sequence) {
            return $this->calcMise($taux, $sequence->getMise());
        }
        if ($this->getNbSequenceOpen() <= UBAlgo::NB_SEQ - 1) {
            echo "New mise Init-------------------- \n";
            return $this->getNewMiseInit();
        }
    }

    public function getNextMise($taux = NULL, $idCategSignal) {
        //verifie si il y a une sequence ouverte
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading($idCategSignal, $this->parameter->getMartinGaleSize());
        /* foreach ($listSequence as $elementKey => $sequenceL) {
          foreach ($this->sequencesToExclude as $sequenceDel) {
          if ($sequenceDel->getId() == $sequenceL->getId()) {
          //delete this particular object from the $array
          unset($listSequence [$elementKey]);
          }
          }
          }
          if (empty($listSequence) && $this->getNbSequenceOpen() <= UBAlgo::NB_SEQ - 1) {
          echo "New mise Init----------- 1 --------- \n";
          return $this->getNewMiseInit();
          } else { */

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

    public function calcMiseForSequence(Sequence $sequence, $taux = NULL) {
        $this->parameter = $this->getLastParameter();
        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        if ($sequence->getMode() == Sequence::MG) {
            echo"MG Active \n";
            $mise = $this->calcMgMise($sequence, $taux);
        } else {
            echo"INFINITY  Active " . $sequence->getId() . "\n";
            $mise = $this->calcMise($taux, $sequence->getMise());
        }
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
            $this->parameter->setBalance($this->parameter->getBalance() + $trade->getAmount() + $trade->getAmountRes());
            $this->parameterPersister->persist($this->parameter);
            $trade->getSequence()->isFinished($this->tradeRepo);
        } else if ($trade->getState() == Trade::STATELOOSE && $trade->getSequenceState() != Trade::SEQSTATEDONE) {
            $this->looseTrade($trade);

            /*
              if($this->parameter->getBalance() < 40) {
              echo "SECURITE COMPTE";
              exit();
              }
             * 
             */
        }
    }

    public function isLastTR(Sequence $sequence) {
        if ($sequence->getPosition() == $sequence->getLengthTrinity()) {
            echo "LAST LOOP --------------\n";
            $sequence->setPosition(0);
            $sequence->setSumLooseTR($sequence->getSumLooseTR() - $sequence->getSumWinTR());
            $sequence->setSumWinTR(0);
            $sequence->setMise(round($sequence->getSumLooseTR() / $sequence->getLengthTrinity(), 2));
            if ($sequence->getMise() < 0.4) {
                $sequence->setMise(0.4);
            }
            $this->sequencePersister->persist($sequence);
        }
    }

    public function winTrade(Trade $trade) {
        $sequence = $trade->getSequence();
        // voir cette partie pour les benefices
        if ($sequence->getMode() == Sequence::MG) {
            $this->winMG($trade, $sequence);
        } else if ($sequence->getMode() == Sequence::TRINITY) {
            $sequence->setMultiWin($sequence->getMultiWin()+1);
            $this->winTR($trade, $sequence);
            $this->isLastTR($sequence);
        }
        // je met à jours le statut du trade gagnant
        $trade->win();
        $this->tradePersister->persist($trade);
    }

    public function winTR(Trade $trade, Sequence $sequence) {
        $sumWinTR = $sequence->getSumWinTR();
        $sequence->setSumWinTR($this->floorDec($sumWinTR + $this->calcSumCatchUp($trade, $sequence)));
        if ($sequence->getSumWinTR() > $sequence->getSumLooseTR()) {
            $sequence->setSumLooseTR(0);
            $sequence->setState(Sequence::CLOSE);
            $this->sequencePersister->persist($sequence);
        }
        $this->modeRouge($sequence);
        $this->modeOrange($sequence);
        
        
        if ($sequence->getMultiWin() == 3) {
            $this->initTrinity($sequence);
            $sequence->setMultiWin(0);
            $this->sequencePersister->persist($sequence);
        }
    }
    
    public function modeOrange(Sequence $sequence) {
        if($sequence->getMultiWin() == 2 && $sequence->getMise() >= ($this->parameter->getBalance() * 0.05)) {
            echo "\n ---- MODE ORANGE ----  \n";
            $this->initTrinity($sequence);
            $sequence->setMultiWin(0);
            $this->sequencePersister->persist($sequence);
            return true;
        }
         echo "\n ---- PAS ORANGE ----  \n";
        return false;
    }
    
    public function modeRouge(Sequence $sequence) {
        if($sequence->getMultiWin() == 1 && $sequence->getMise() >= ($this->parameter->getBalance() * 0.1)) {
            echo "\n ---- MODE ROUGE ----  \n";
            $this->initTrinity($sequence);
            return true;
        }
        echo "\n ---- PAS ROUGE ----  \n";
        return false;
    }

    public function initTrinity(Sequence $sequence) {
        $sequence->setPosition(0);
        $sequence->setSumLooseTR($sequence->getSumLooseTR() - $sequence->getSumWinTR());
        $sequence->setSumWinTR(0);
        $sequence->setMise(round($sequence->getSumLooseTR() / $sequence->getLengthTrinity(), 2));
        if ($sequence->getMise() < 0.4) {
            $sequence->setMise(0.4);
        }
        $this->sequencePersister->persist($sequence);
    }

    public function calcSumCatchUp(Trade $trade, Sequence $sequence) {

        $profit = $this->floorDec($trade->getAmountRes() - ($sequence->getMise() / 0.95));
        if ($profit > 0) {
            $sequence->setProfit($sequence->getProfit() + $profit);
        }
        $this->sequencePersister->persist($sequence);
        return $trade->getAmountRes() - $profit;
    }

    public function floorDec($nb) {
        return floor($nb * 100) / 100;
    }

    public function winMG(Trade $trade, Sequence $sequence) {
        if ($sequence->getSumWin() > $sequence->getSumLoose()) {
            echo "Sum win > sum loose Close Seq\n";
            $sequence->initTradeWin($this->tradeRepo);
            $sequence->isFinished();
            $this->sequencePersister->persist($sequence);
            if ($sequence->getLength() == 1) {
                $this->checkJoker();
            }
            return;
        }

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
        $trade->setState(Trade::STATELOOSE);
        $sequence = $trade->getSequence();

        if ($sequence->getLength() == $this->parameter->getMartingaleSize()) {
            $amount = round($this->getSumToRecup($sequence), 2);
            $sequence->setMode(Sequence::TRINITY);
            $sequence->setSumLooseTR($amount);
            $sequence->setMise(round($amount / $sequence->getLengthTrinity(), 2));
            $sequence->setPosition(0);
            $this->sequencePersister->persist($sequence);
        }
        if ($sequence->getMode() == Sequence::TRINITY) {
            echo "\n INCREME SUM LOOSE -------------->" . $sequence->getSumLooseTR() + $trade->getAmount() . "<-----------------\n";
            $sequence->setMultiWin(0);
            $sequence->setSumLooseTR($this->floorDec($sequence->getSumLooseTR() + $trade->getAmount()));
            $this->sequencePersister->persist($sequence);
        }
        $this->isLastTR($sequence);
        $this->tradePersister->persist($trade);
    }

    public function getNewMiseInit() {

        $balance = $this->parameter->getBalance();

        if ($balance >= 1000) {
            echo "compte : $balance \n";
            //return UBAlgo::DEFAULT_MISE;
            return round(($balance * 0.0075) / 3.2, 2);
        } else {
            return UBAlgo::DEFAULT_MISE;
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

    // initialise tous les signaux non pris a 1 pour eviter les mauvais signaux
    public function initTradeSignalBegin() {
        $listSignal = $this->signalRepo->findByIsTrade(0);
        foreach ($listSignal as $signal) {
            $signal->setIsTrade(1);
            $this->tradeSignalPersister->persist($signal);
        }
    }

    public function getSumToRecup(Sequence $sequence) {
        $trades = $this->tradeRepo->getTradeForSequence($sequence);
        $amount = 0;
        foreach ($trades as $trade) {
            if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                $amount += $trade->getAmount();
            }
        }
        return $amount;
    }

}
