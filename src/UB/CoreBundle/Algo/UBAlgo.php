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

    const DEFAULT_MISE = 0.40;
    const DEFAULT_OFA_TAUX = 0.82;
    const DEFAULT_OFA_MIN= 1.5;
    const MISE_RECUP = 0.5;
    const MISE_MULTIWIN = 2.5;
    const NB_SEQ = 2;
    const PALIER_CRITIQUE = 45;
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
        /* if ($sequence->getLength() == 1) {
          $trade->setSequenceState(Trade::SEQSTATEFIRST);
          } else if ($this->parameter->isMgActive()) {
          if ($sequence->getLength() < $this->parameter->getMartingaleSize()) {
          $trade->setSequenceState(Trade::SEQSTATEMARTING);
          } else if ($sequence->getLength() == $this->parameter->getMartingaleSize()) {
          $trade->setSequenceState(Trade::SEQSTATELASTMARTING);
          } else {
          $trade->setSequenceState(Trade::SEQSTATEUNDONE);
          }
          } else { */
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
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading('PUT');
        foreach ($listSequence as $sequence) {
            return $sequence->getMode();
        }
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading('CALL');
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
            //   echo "non empty list signal \n";
            //New signal

            foreach ($listSignals as $signal) {
                if (($this->getModeSequence() == Sequence::REVERSE && $this->isTradingFinish() && $this->getNbSequenceOpen() <= UBAlgo::NB_SEQ) || $this->getNbSequenceOpen() == 0) {
                    echo "TEST\n";
                    $this->execNewSignal($conn, $api, $signal);
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

    public function execNewSignal($conn, $api, $signal) {
        if ($this->isTradingFinish()) {
            $rate = $this->getRateSignal($signal);
            $amountPut = $this->getNextMise($signal->getCategorySignal()->getId(), 'PUT', $this->getBestRate($rate));
            $amountCall = $this->getNextMise($signal->getCategorySignal()->getId(), 'CALL', $this->getBestRate($rate));
            // TODO calculer normalement les mises pour les OFA ne pas oublier de prendre le last OFA de l'autre sequence 
            // Si lastofa = 0 ajouter 1.5 a la mise
            // puis appliquer les strategie sur les WIN pour repartir les gains pour diminuer les sumlooseTR
            
            $trade1 = $this->createNewTradeForApi($signal, $amountPut, Trade::SEQSTATEUNDONE);
            $sequencePut = $this->getSequenceForTrade($trade1, 'PUT');
            $signal->setContractType('CALL');
            $trade = $this->createNewTradeForApi($signal, $amountCall, Trade::SEQSTATEUNDONE);
            $sequenceCall = $this->getSequenceForTrade($trade, 'CALL');
            // je tente de récupérer une partie des pertes de l'autre sequence si un des deux tente un OFA ou est sur son 2nd trade
            $amountCall += $this->calcSumToBalanceSequence($sequenceCall, $sequencePut);
            $amountPut += $this->calcSumToBalanceSequence($sequencePut, $sequenceCall);
            $trade1->setAmount(round($amountPut,2));
            $trade->setAmount(round($amountCall,2));
            
            
            
            if ($amountPut > 0 AND $amountCall > 0) {



                // Link to the sequence and set to 1 the length
                $trade1->setSequence($sequencePut);
                $this->tradePersister->persist($trade1);
                // new api call with mise to send the trade


                // Link to the sequence and set to 1 the length
                $trade->setSequence($sequenceCall);
                $this->tradePersister->persist($trade);
                // new api call with mise to send the trade
                $this->doMise($trade, $conn, $api);
                $this->doMise($trade1, $conn, $api);
                echo "MISE ENVOYER ENVOYER API\n";
            }
        }
    }
    
    public function calcSumToBalanceSequence(Sequence $sequence1, Sequence $sequence2) {
        $sumLoose = $sequence1->getSumLooseTR() - $sequence1->getSumWinTR();
        if($sequence1->getMise() > $sumLoose){
            $sequence1->setMise($sumLoose);
            $this->sequencePersister->persist($sequence1);
        }
        if($sequence1->getModeMise() == 2  ) {
            if ($sequence2->getMise() > 0){
                ECHO "********** AJOUT MISE LAST Mise ********\n";
                return round($sequence2->getMise(),2);
            }
        }
    }

    public function execNewSignalSecure($conn, $api, $signal) {
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

    public function doMise(Trade $trade, $conn, $api) {
        $this->parameterPersister->persist($this->parameter);
        // $this->entityManager->detach($this->parameter);
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
                $res = 0.85;
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
        return round($this->parameter->getBalance() / 200, 2);
    }

    public function getSequenceForTrade(Trade $trade, $sens) {
        //Retourne toutes les sequences ouvertes
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading($sens);
        //TODO ajouter parametre PUT ou CALL
        if (empty($listSequence)) {
            //   echo " ------ Pas de sequence Ouverte dispo \n";

            $sequence = $this->sequencePersister->newSequence(1, 0, ($this->parameter->getBalance()), $sens);
            //$this->initTrinity($sequence);
            $this->sequencePersister->persist($sequence);
            return $sequence;
        } else {
            // echo " ----- Sequence Ouverte dispo \n";
            foreach ($listSequence as $sequence) {
                //if ($this->calcMiseForSequence($sequence, $this->getBestRate($trade->getRate())) == $trade->getAmount()) {
                //     echo "---******- idSequence " . $sequence->getId() . ' taux : ' . $this->getRateTrade($trade) . " ----\n";
                return $sequence;
                //  }
            }
            /* if ($this->getNbSequenceOpen() > 0){
              //
              }else */
            return $this->sequencePersister->newSequence(1, 0, $this->parameter->getBalance() + $trade->getAmount(), $sens);
        }
    }

    public function createNewTradeForApi(TradeSignal $signal, $amount, $mode = Trade::SEQSTATEUNDONE) {

        $parameter = $this->parameterRepo->findOneBy(array('id' => Parameter::DEFAULT_ID));

        $trade = new Trade();
        if ($amount < 0.35)
            $amount = UBAlgo::DEFAULT_MISE;
        $trade->setAmount($amount);
        $trade->setSymbole($signal->getSymbole());
        $trade->setDuration($signal->getDuration());
        $trade->setCurrency($parameter->getCurrency());
        $trade->setContractType($signal->getContractType());
        $trade->setState(Trade::STATETRADE);
        if ($mode == Trade::SEQSTATESECURE) {
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
            if ($sequence->getMultiLoose() >= 3) {
                return UBAlgo::DEFAULT_MISE;
            }
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

    public function getNextMise($idCategSignal, $sens, $taux = NULL) {
        //verifie si il y a une sequence ouverte
        $this->parameter = $this->getLastParameter();
        $listSequence = $this->sequenceRepo->getOpenSequenceNotTrading($sens, $idCategSignal, $this->parameter->getMartinGaleSize());

        if ($taux == NULL) {
            $taux = $this->parameter->getDefaultRate();
        }
        foreach ($listSequence as $sequence) {
            //$sequence->isFinished($this->tradeRepo, $this->parameter->getBalance(), $sequence->getBalanceStart());
            //   $this->sequencePersister->persist($sequence);
            // $this->sequencesToExclude[] = $sequence;
            return $this->calcMiseForSequence($sequence, $taux);
        }
        if ($this->getNbSequenceOpen() <= UBAlgo::NB_SEQ - 1) {
            echo "New mise Init-------------------- \n";
            return $this->getNewMiseInit($sens);
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
        if ($sequence->getMode() == Sequence::REVERSE) {
            $mise = $this->calcReverseMise($sequence, $taux);
        }

        if ($mise > 0) {
            return $mise;
        } else {

            throw new \Exception('- 1 Erreur BDD Sequence ouverte avec rien a ratrapper !');
        }
    }

    public function checkSecurityOut(Sequence $sequence, $sens) {
        $trades = $this->tradeRepo->getLastFourTrade($sens, $sequence);
        $trade1 = 'WIN';
        $trade2 = 'LOOSE';
        $trade3 = 'WIN';
        $trade4 = 'WIN';

        $i = 1;
        foreach ($trades as $trade) {
            if ($trade->getState() != ${'trade' . $i}) {
                return false;
            }
            $i++;
        }
        if ($i < 4) {
            return false;
        }
        return true;
    }

    public function setSumTalentRecupWithOFA(Sequence $sequence, $lastAmountTrade) {
        $lastOFA = round(($sequence->getLastOFA() / (UBAlgo::DEFAULT_OFA_TAUX)) + 0.01, 2);
        if ($lastAmountTrade == $lastOFA) {
            $res90 = (round(($sequence->getLastOFA() / (0.9)) + 0.01, 2) * 0.94);
            $res85 = ($lastOFA * 0.94);
            $sumRecup = $res85 - $res90;
            $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() - $sumRecup);
            $this->parameterPersister->persist($this->parameter);
        }
    }

    public function setResultTrade(Trade $trade) {
        $this->parameter = $this->getLastParameter();
        if ($trade->getState() == Trade::STATEWIN && $trade->getSequenceState() != Trade::SEQSTATEDONE) {
            // echo "Wintrade ! \n";
            $this->winTrade($trade);
            $sequence = $trade->getSequence();
            $oppositeSeq = $this->sequenceRepo->getLastOpenSequenceSens($this->getOppositeSens($sequence));
            $this->fusionReverseLooseNextMise($sequence);
            $sequence->setModeMise(2);
            $this->parameter->setTalent(0);
            $sequence->setMultiLoose(0);
            $this->parameter->setSumRecupSecour(0);


            //echo "Update balance ! \n";

                $sequence->setSumWinTR($sequence->getSumWinTR() + $trade->getAmountRes());
                $this->parameter->setBalance($this->parameter->getBalance() + $trade->getAmount() + $trade->getAmountRes());
            
            $this->parameterPersister->persist($this->parameter);
           
            $isFinish = $this->isSequenceFinish($sequence);
            $this->isSequenceFinish($oppositeSeq);
            
            if ($isFinish) {
                //$oppositeSeq = $this->sequenceRepo->getLastOpenSequenceSens($this->getOppositeSens($sequence));
                //$this->balanceSeq($sequence, $oppositeSeq);
                $this->parameter->setLastLengthSequence($sequence->getLength());
                $this->parameterPersister->persist($this->parameter);
            }
           // $this->checkOFASecure($sequence->getSumLooseTR(), $sequence);
        } else if ($trade->getState() == Trade::STATELOOSE && $trade->getSequenceState() != Trade::SEQSTATEDONE) {

            $this->looseTrade($trade);
            $sequence = $trade->getSequence();
            $sequence->setMultiWin(0);
            $sequence->setModeMise(2); //DEFAULT
            
              if ($sequence->getMultiLoose() == 1 && $sequence->getLength() < 2) {
                $sequence->setModeMise(1);
              } 

            $this->parameterPersister->persist($this->parameter);
            /* if ($this->parameter->getTalent() == 1){
              $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() + UBAlgo::DEFAULT_MISE );
              $this->parameter->setTalent(0);
              } */

                $sequence->setSumLooseTR($sequence->getSumLooseTR() + $trade->getAmount());
            $this->isSequenceFinish($sequence);

             $this->checkLooseBigFirstTrade($trade, $sequence);
            // $this->checkOFASecure($sequence->getSumLooseTR(), $sequence);
        }
        // $sequence->setSumLooseTR($sequence->getSumLooseTR() + UBAlgo::MODE_ROUGE);
        $this->sequencePersister->persist($sequence);
    }
    
    public function balanceSeq(Sequence $sequenceClose, Sequence $sequenceOpen = NULL){
        $surplus = $sequenceClose->getSumWinTR() - $sequenceClose->getSumLooseTR();
        
        if ( $surplus > 0.5  && $sequenceOpen != NULL){
            echo 'Sequence '.$sequenceClose->getId()." sur plus : ".($surplus -0.5)."\n";
            $sequenceOpen->setSumWinTR($sequenceOpen->getSumWinTR() + ($surplus - 0.5));
            $sequenceClose->setSumWinTR($sequenceClose->getSumWinTR() - ($surplus - 0.5));
            $this->sequencePersister->persist($sequenceOpen);
            $this->sequencePersister->persist($sequenceClose);
            //$this->isSequenceFinish($sequenceOpen);
        }
    }
    
    public function getOppositeSens(Sequence $sequence){
        if ($sequence->getSens() == 'CALL'){
            return 'PUT';
        } else {
            return 'CALL';
        }
    }

    public function checkPalier() {
        // met en place une securite pour les bad sequence
        $sumLost = $this->parameter->getTalent() - $this->parameter->getBalance();
        if ($sumLost > UBAlgo::PALIER_CRITIQUE) {
            $subPalier = $sumLost - UBAlgo::PALIER_CRITIQUE;
            $this->parameter->setSumRecupSecour($this->parameter->getSumRecupSecour() + $subPalier);
            // $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() - $subPalier); 
            $this->parameter->setTalent($this->parameter->getTalent() - $subPalier);
            $this->parameter->setIsActiveM5(1); // TODO renomer le flag
            $this->parameterPersister->persist($this->parameter);
        }
    }

    public function checkLooseBigFirstTrade(Trade $trade, Sequence $sequence) {
        if ($sequence->getMultiLoose() == 1 && $sequence->getLength() == 1 && $sequence->getSumLooseTR() == (UBAlgo::DEFAULT_MISE + UBAlgo::MISE_MULTIWIN)) {
            $sequence->setLastOFA(2.5);
            $this->sequencePersister->persist($sequence);
            return true;
        }
        return false;
    }

    public function checkOFASecure($amount, Sequence $sequence) {
        if ($amount >= 120 || $this->calcReverseMise($sequence, 0.84) > 75) {
            $sequence->setState(Sequence::CLOSE);
            $this->parameter->setLastLengthSequence($sequence->getLength());
            $this->parameterPersister->persist($this->parameter);
            $this->sequencePersister->persist($sequence);
            $this->addSumLoose($sequence);
        }
    }

    public function getStatLooseSequence(Sequence $sequence) {
        $trades = $this->tradeRepo->getTradeForSequence($sequence);
        $nbLoose = 0;
        $nbTrade = 0;

        foreach ($trades as $trade) {
            if ($trade->getState() == Trade::STATELOOSE) {
                $nbLoose++;
            }
            $nbTrade++;
        }
        return $nbLoose / ($nbTrade / 100);
    }

    public function addSumLoose(Sequence $sequence) {
        $sumLoose = $sequence->getSumLooseTR() - $sequence->getSumWinTR();
        echo "##### SUMLOOSE $sumLoose #####\n";
        $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() + $sumLoose);
        $this->parameterPersister->persist($this->parameter);
    }

    public function looseSecure(Trade $trade, Sequence $sequence) {
        $trade->setState(Trade::STATELOOSE);
        $trade->setSequenceState(Trade::SEQSTATEDONE);
        $this->tradePersister->persist($trade);
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
        //     echo "MULTI WIN ++++++\n";
        $sequence->setMultiWin($sequence->getMultiWin() + 1);
        /* if ($this->isSequenceFinish($sequence, ($trade->getAmountRes() - $trade->getAmount()))) {
          return true;
          } */
        if ($sequence->getMode() == Sequence::REVERSE) {
            //       echo "WIN REVERSE\n";
            $this->martinGWin($trade, $sequence);
            // $this->fusionReverseLoose($sequence, $trade);
        }
        // je met à jours le statut du trade gagnant
        $trade->win();
        $this->tradePersister->persist($trade);
        $this->sequencePersister->persist($sequence);
    }

    public function fusionReverseLooseNextMise(Sequence $sequence) {
        $trades = $this->tradeRepo->getTradeForSequence($sequence);
        $i = 0;
        $amount = 0;
        if ($this->checkFusion4($sequence)) {
            $break = 4;
        } else {
            $break = $this->parameter->getMartingaleSize();
        }
        foreach ($trades as $trade) {
            if ($i == $break) {
                break;
            }
            if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                $amount += $trade->getAmount();
                echo "recupère la valeure a recup " . $amount . "\n";
                $i++;
            }
        }
        $sequence->setMise($amount);
        $this->sequencePersister->persist($sequence);
    }

    public function checkFusion4(Sequence $sequence) {
        if ($sequence->getLength() >= 5 && $sequence->getSumLooseTR() < 4) {
            return true;
        } else {
            return false;
        }
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

    public function isSequenceFinish(Sequence $sequence) {
        $diff = $sequence->getSumWinTR() - $sequence->getSumLooseTR();
        if ($diff > 0) {
            echo "###CLOSE ISFINISHED###  $diff  id : ";
            echo $sequence->getId()."\n";
            $sequence->setState(Sequence::CLOSE);
            $sequence->setTimeEnd(new \DateTime());
            $this->sequencePersister->persist($sequence);
            $this->parameter->setLastLengthSequence($sequence->getLength());
            $this->parameterPersister->persist($this->parameter);
            $oppositeSeq = $this->sequenceRepo->getLastOpenSequenceSens($this->getOppositeSens($sequence));
            $this->balanceSeq($sequence, $oppositeSeq);
            return true;
        }
        return false;
    }

    public function modeOrange(Sequence $sequence) {
        if ($sequence->getMultiWin() == 2) {// orange actif tous le temps ancienne condtion : $sequence->getMise() >= ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)
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
        if ($sequence != NULL && $sequence->getMode() == Sequence::TRINITY) {
            if ($sequence != NULL && $sequence->getSumLooseTR() >= ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)) {
                echo "MODE_ORANGE ----- TRINITY  \n";
                return true;
            }
        } else {
            if ($sequence != NULL && $sequence->getSumLoose() >= ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE)) {
                echo "MODE_ORANGE -----SUM LOOSE => " . $sequence->getSumLoose() . "  MONTANT ORANGE => " . ($this->parameter->getBalance() * UBAlgo::MODE_ORANGE) . " \n";
                return true;
            }
        }
        return false;
    }

    public function modeRouge(Sequence $sequence) {
        if ($sequence->getMultiWin() == 1 && $sequence->getPosition() == 1 && $sequence->getMise() >= ($this->parameter->getBalance() * UBAlgo::MODE_ROUGE)) {
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

    public function updateLastUndoneTrade(Sequence $sequence) {
        echo "Maj Trade non ratrape dans sequence  \n";
        $undoneTrade = $sequence->getNextUndoneTrade($this->tradeRepo);
        $undoneTrade->setSequenceState(Trade::SEQSTATEDONE);
        $this->tradePersister->persist($undoneTrade);
    }

    public function isStillSecureMode(Sequence $sequence) {
        echo "IS STILL SECURE \n";
        $undoneTrade = $this->tradeRepo->getUndoneTradeOrNull($sequence);
        if ($undoneTrade != NULL) { // toujours en mode SECURE
            echo "YES \n";
            return $undoneTrade->getAmount();
        } else { // plus en mode secure je rebascule en EVO
            echo "NO \n";
            $sequence->setMode(Sequence::EVO);
            $this->sequencePersister->persist($sequence);
            return 0;
        }
    }

    public function winSecure(Trade $trade, Sequence $sequence) {
        //incrementer sumwin        
        // si somme gagner moins somme perdu sur ce bloque superieur à somme a recup cloture

        $sequence->setSumWinTR($sequence->getSumWinTR() + $trade->getAmountRes());
        $this->sequencePersister->persist($sequence);
        $this->checkEndTrinitySecure($sequence, $trade); //check fin trinity Secure
    }

    public function checkEndTrinitySecure(Sequence $sequence) {
        $length = $sequence->getMultiLoose() + $sequence->getMultiWin();
        echo "######### Check end Trinity Secure #######\n";
        if ($length == 10) {
            if ($sequence->getSumWinTR() > $sequence->getSumLooseTR() && $this->parameter->getSumLooseTalent() > 0) {
                $sumWin = $sequence->getSumWinTR() - $sequence->getSumLooseTR();
                $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() - $sumWin);
            }
            if ($sequence->getSumWinTR() < $sequence->getSumLooseTR()) {
                $sumLoose = $sequence->getSumLooseTR() - $sequence->getSumWinTR();
                $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() + $sumLoose);
            }
            if ($sequence->getStatTenLastTrade() >= 50) {
                $this->initSecureMode($sequence);
                $sequence->setMode(Sequence::EVO);
                $this->sequencePersister->persist($sequence);
            }
            $this->parameterPersister->persist($this->parameter);
        }/*
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
          } */
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
        //echo "LOOOOSE\n";
        $trade->setState(Trade::STATELOOSE);
        $sequence = $trade->getSequence();
        $sequence->setMultiLoose($sequence->getMultiLoose() + 1);
        //$this->looseTalent($trade, $trade->getSequence());
        $this->tradePersister->persist($trade);
        /*
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
          } */
        //$this->looseTalent($trade, $trade->getSequence());
        // 
        /*
          if ($sequence->getMode() == Sequence::EVO) {
          $this->checkSecureMode($sequence);  //appele la fonction qui initialialise la sequence en secure si necessaire
          } */
        $sequence->initMultiEveryTenTrade();
        $this->sequencePersister->persist($sequence);
    }

    public function checkSecureMode(Sequence $sequence) {
        // initialise la trnity secure
        if ($sequence->getMultiLoose() >= 4 && $sequence->getMultiWin() == 0 || $sequence->getStatTenLastTrade() < 50) {
            $this->initSecureMode($sequence);
        }
        // si mise pas ratrappé je ne fais rien pour continuer a recuperer ma somme perdu
    }

    public function initSecureMode(Sequence $sequence) {

        $sequence->setMode(Sequence::SECURE);
        $sequence->setSumToRecup(0);
        $sequence->setSumLooseTR(0);
        $sequence->setSumWinTR(0);
        $sequence->setMise(0.35);
        $this->sequencePersister->persist($sequence);
    }

    public function looseTalent(Trade $trade, Sequence $sequence) {
        if ($sequence->getLength() == 1 && $trade->getAmount() > UBAlgo::DEFAULT_MISE) {
            echo "######## LOOSE TALENT SET SUMLOOSETALENT #######";
            $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() + 0.46); //TODO mise defaut x taux devise
            $this->parameterPersister->persist($this->parameter);
            $this->sequencePersister->persist($sequence);
            // $trade->setAmount(UBAlgo::DEFAULT_MISE); TODO
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
            $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() - 0.46); // TODO
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
        $halfAmount = round($trade->getAmount() / 2, 2);
        $this->checkHalfAmount($sequence, $trade, $halfAmount);
        $sumToRepart = round($halfAmount / $nbLoose, 2); // -1 pour retirer la première mise a ratrapper
        echo "DEMI SUMTOREPART : $sumToRepart nbtradeLoose : " . $nbLoose . '\n';
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart);
    }

    public function checkHalfAmount(Sequence $sequence, Trade $trade, $halfAmount) {
        if ($sequence->checkHalfTradeAndFull($this->tradeRepo, $this->tradePersister, $halfAmount)) {
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
        echo "FULL SUMTOREPART : " . $trade->getAmount() . " nbtradeLoose : " . ($nbLoose - 1) . '\n';
        $sumToRepart = round($trade->getAmount() / ($nbLoose - 1), 2);
        $sequence->repartValueOnUndone($this->tradeRepo, $this->tradePersister, $sumToRepart);
    }

    public function looseMod7(Trade $trade, Sequence $sequence, $nbLoose) {
        $this->sequencePersister->persist($sequence);
        $halfAmount = round($trade->getAmount() / 2, 2);
        $sumToRepart = round($halfAmount / $nbLoose, 2); // -1 pour retirer la première mise a ratrapper
        echo "DEMI SUMTOREPART : $sumToRepart nbtradeLoose : " . $nb . '\n';
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

    public function getNewMiseInit($sens) {

        $this->parameter = $this->getLastParameter();
        $mise = 0;
        $lastLength = $this->sequenceRepo->getLastCloseSequenceSensLength($sens);
        //TODO a reflechir pour continuer sequence win
        if ($lastLength == 1) {
            $oppositeSeq = $this->sequenceRepo->getLastOpenSequenceSens($this->getOppositeSensChar($sens));
            if ($oppositeSeq != NULL){
                $lastMise = round($oppositeSeq->getMise(),2);
            } else {
                $lastMise = 0;
            }
            $mise += ($lastMise > UBAlgo::MISE_MULTIWIN)? $lastMise : UBAlgo::MISE_MULTIWIN;
        }

        return UBAlgo::DEFAULT_MISE + $mise;
    }
    
    public function getOppositeSensChar($sens){
        if ($sens == 'PUT') {
            return 'CALL';
        } else {
            return 'PUT';
        }
    }

    public function checkSecuritySequenceToRecup() {
        if ($this->parameter->getSumLooseTalent() > 0 && $this->parameter->getSumLooseTalent() < 7 && $this->parameter->getIsActiveM5() == 0 && $this->parameter->getSumRecupSecour() > 0) {// TODO renomer isactiveM5 en flag Security
            echo "\n######## NEW MISE INIT CHECKSECURITY OK #######\n";
            $this->setSumRecupData();
        }
    }

    public function checkSecuritySequence(Sequence $sequence, $test = 0) {
        if ($this->parameter->getSumLooseTalent() > 0 && $this->parameter->getSumLooseTalent() < 7 && $this->parameter->getIsActiveM5() == 0 && $this->parameter->getSumRecupSecour() > 0 && $test == 0 && $sequence->getSumLooseTR() < 5) {// TODO renomer isactiveM5 en flag Security
            $this->setSumRecupData();
        }
    }

    public function setSumRecupData() {
        if ($this->parameter->getSumRecupSecour() > 2) {
            $sum = UBAlgo::MISE_RECUP;
        } else {
            $sum = $this->parameter->getSumRecupSecour();
        }
        $this->parameter->setSumLooseTalent($this->parameter->getSumLooseTalent() + $sum);
        $this->parameter->setSumRecupSecour($this->parameter->getSumRecupSecour() - $sum);
        $this->parameter->setTalent($this->parameter->getTalent() + $sum);
        if ($this->parameter->getSumLooseTalent() > 5 && $this->parameter->getSumRecupSecour() > 0) {
            $this->parameter->setIsActiveM5(1);
        }
        $this->parameterPersister->persist($this->parameter);
        return $sum;
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

    public function calcReverseMise(Sequence $sequence, $taux) {
        echo "debug calc nextMg id " . $sequence->getId() . "\n";
        $trades = $this->tradeRepo->getTradeForSequence($sequence);
        $mise = $this->modeCalcMise($sequence, $trades);
        $mise = ($mise > 0.35) ? round(($mise / ($taux)) + 0.01, 2) : 0.35;
        echo $mise . " ****** REVERSE test taux " . $taux . " \n";
        return $mise;
    }

    public function modeCalcMise(Sequence $sequence, $trades) {
        $mise = 0;

        echo "--- MODE --" . $sequence->getModeMise() . "  ****\n";

        switch ($sequence->getModeMise()) {
            case 1 :
                foreach ($trades as $trade) {
                    if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                        $mise += $trade->getAmount();
                        echo "# 1 #######  recupère la valeure a recup " . $mise . "\n";
                        break;
                    }
                }
                if ($mise == 0.35 && $sequence->getLength() < 2)
                    $mise = 0.39;
                break;
            case 2 :
                $sumLoose = $sequence->getSumLooseTR() - $sequence->getSumWinTR();
                $mise = $sequence->getMise();
                if ($mise > $sumLoose){
                    $mise = $sumLoose;
                }
                break;
        }
        $this->sequencePersister->persist($sequence);
        return $mise;
    }

    public function addSumToRecup() {
        $mise = 0;
        if ($this->parameter->getSumLooseTalent() > 0) {
            $mise += UBAlgo::MISE_RECUP;
            echo "######## add sum_recup SUMLOOSETALENT #######\n";
            $sumLT = $this->parameter->getSumLooseTalent() - (UBAlgo::MISE_RECUP * 0.94);
            if ($sumLT < 0) {
                $sumLT = 0;
            }
            $this->parameter->setSumLooseTalent($sumLT);
            $this->parameter->setSumRecupSecour(1);
            $this->parameterPersister->persist($this->parameter);
        }
        return $mise;
    }

    public function calcEvoMise(Sequence $sequence, $taux) {

        echo "debug calc Evo id " . $sequence->getId() . "\n";
        $trades = $this->tradeRepo->getTradeForSequence($sequence);
        echo "DEBUG 1\n";
        $amount = 0;

        foreach ($trades as $trade) {
            if ($trade->getSequenceState() != Trade::SEQSTATEDONE && $trade->getState() != Trade::STATEWIN) {
                $amount += $trade->getAmount();
                echo "recupère la valeure a recup " . $amount . "\n";
                break;
            }
        }
        echo "DEBUG 2\n";
// SECURITE si mise supérieur a 10 euro
        $taux = ($amount > 2) ? 0.92 : $taux; // TODO transformer 10 en une variable avec un ration par rapport au compte
        // Securite last trade too big
        $nbLoose = $sequence->getNbLooseEvo($this->tradeRepo) + 1;
        if ($nbLoose == 1 && $sequence->getLength() > 20) {
            $amountTmp = $sequence->getBalanceStart() - $this->parameter->getBalance() + ($sequence->getLength() * 0.035);
            $amount = ($amountTmp < $amount) ? $amountTmp : $amount;
        }
        echo "DEBUG 3\n";
        if (round(($amount / ($taux)) + 0.01, 2) < 0.5 && $amount > 0) {
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
        if ($sequence->getMode() == Sequence::TRINITY) {
            echo "SUM TO RECUP " . $sequence->getSumLooseTR() . "\n";
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
        echo "SUM TO RECUP" . $amount . "\n";
        return $amount;
    }

}
