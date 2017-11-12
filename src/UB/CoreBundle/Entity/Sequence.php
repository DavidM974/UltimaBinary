<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use \UB\CoreBundle\Repository\TradeRepository;

/**
 * Sequence
 *
 * @ORM\Table(name="sequence")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\SequenceRepository")
 */
class Sequence
{
    const OPEN = 'OPEN';
    const CLOSE = 'CLOSE';
    const PAUSE = 'PAUSE';
    const MG = 'MG';
    const TRINITY = 'TRINITY';
    const THEOPHILE = 'THEOPHILE';
    const EVO = 'EVO';
    const SECURE = 'SECURE';
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="length", type="smallint", nullable=true)
     */
    private $length;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string" , columnDefinition="ENUM('OPEN', 'CLOSE', 'PAUSE')")
     */
    private $state;

    /**
     * @var string
     *
     * @ORM\Column(name="mode", type="string" , columnDefinition="ENUM('MG', 'EVO', 'SECURE')")
     */
    private $mode;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_start", type="datetime")
     */
    private $timeStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_end", type="datetime", nullable=true)
     */
    private $timeEnd;
    
    /**
    * @ORM\OneToMany(targetEntity="UB\CoreBundle\Entity\Trade", mappedBy="sequence", fetch="EAGER")
    * @ORM\OrderBy({"amount" = "ASC"})
    */
    private $trades;
    
    /**
     * @var int
     *
     * @ORM\Column(name="length_trinity", type="smallint", nullable=true)
     */
    private $lengthTrinity;   

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="smallint", nullable=true)
     */
    private $position;
    
     /**
     * @var int
     *
     * @ORM\Column(name="multiWin", type="smallint", nullable=true)
     */
    private $multiWin;
    
    /**
     * @var int
     *
     * @ORM\Column(name="multiLoose", type="smallint", nullable=true)
     */
    private $multiLoose;
    
    /**
     * @var float
     *
     * @ORM\Column(name="sum_loose_tr", type="float", nullable=true)
     */
    private $sumLooseTR;
    
    /**
     * @var float
     *
     * @ORM\Column(name="sum_win_tr", type="float", nullable=true)
     */
    private $sumWinTR;
    
    /**
     * @var float
     *
     * @ORM\Column(name="sumToRecup", type="float", nullable=true)
     */
    private $sumToRecup;
    
    
     /**
     * @var float
     *
     * @ORM\Column(name="mise", type="float", nullable=true)
     */
    private $mise;
    
     /**
     * @var float
     *
     * @ORM\Column(name="balanceStart", type="float", nullable=true)
     */
    private $balanceStart;
    
    public function __construct()
    {
      $this->trades = new ArrayCollection();
    }
    

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Set balanceStart
     *
     * @param float $balanceStart
     *
     * @return Parameter
     */
    public function setBalanceStart($balanceStart)
    {
        $this->balanceStart = $balanceStart;

        return $this;
    }

    /**
     * Get balanceStart
     *
     * @return float
     */
    public function getBalanceStart()
    {
        return $this->balanceStart;
    }

    /**
     * Set length
     *
     * @param integer $length
     *
     * @return Sequence
     */
    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Get length
     *
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Sequence
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set mode
     *
     * @param string $mode
     *
     * @return Sequence
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get mode
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }
    
    /**
     * Set timeStart
     *
     * @param \DateTime $timeStart
     *
     * @return Sequence
     */
    public function setTimeStart($timeStart)
    {
        $this->timeStart = $timeStart;

        return $this;
    }

    /**
     * Get timeStart
     *
     * @return \DateTime
     */
    public function getTimeStart()
    {
        return $this->timeStart;
    }

    /**
     * Set timeEnd
     *
     * @param \DateTime $timeEnd
     *
     * @return Sequence
     */
    public function setTimeEnd($timeEnd)
    {
        $this->timeEnd = $timeEnd;

        return $this;
    }

    /**
     * Get timeEnd
     *
     * @return \DateTime
     */
    public function getTimeEnd()
    {
        return $this->timeEnd;
    }
    
    public function addLength() {
        $this->length++;
    }
    
    public function addTrade(Trade $trade)
    {
      $this->trades[] = $trade;
      $this->addLength();
    }

    public function removeTrade(Trade $trade)
    {
      $this->trades->removeElement($trade);
    }

    public function getTrades()
    {
      return $this->trades;
    }
    
    
        
        // récupère la prochaine mise de la séquence non rattrapé
    public function getNextAmountSequence(TradeRepository $tradeRepo) {
        echo "getNextAmountSequence \n";

          $trades = $tradeRepo->findBySequence($this);

          foreach ($trades as $trade) {
              // retourne la première mise 
              if($trade->getSequenceState() != Trade::SEQSTATEDONE){
                return $trade->getAmount();
              }
          }
          //si pas de mise pour cette séquence
          
        return 0;
    }
    
    
            // récupère la prochaine mise de la séquence non rattrapé
    public function getNextAmountSequenceMg(TradeRepository $tradeRepo) {
        echo "getNextAmountSequence MG\n";

          $trades = $tradeRepo->findBySequence($this);
          $sumLoose = 0;
          foreach ($trades as $trade) {
              // retourne la première mise 
              if($trade->getSequenceState() == Trade::SEQSTATEFIRST || $trade->getSequenceState() == Trade::SEQSTATEMARTING){
                $sumLoose += $trade->getAmount();
              }
          }
          
          if ($sumLoose > 0) {echo "Recupere les trades avant LMG\n"; return $sumLoose; }
          
          foreach ($trades as $trade) {
              // retourne la première mise 
              if($trade->getSequenceState() == Trade::SEQSTATEUNDONE || $trade->getSequenceState() == Trade::SEQSTATELASTMARTING){
                return $trade->getAmount();
              }
          }
          //si pas de mise pour cette séquence
          
        return 0;
    }
    
    public function getLastMgSequence(TradeRepository $tradeRepo) {

          $trades = $tradeRepo->findBySequence($this);
          foreach ($trades as $trade) {
              // retourne la première mise 
              if($trade->getSequenceState() == Trade::SEQSTATELASTMARTING) {
                return $trade->getAmount();
              }
          }
          //si pas de mise pour cette séquence
        return 0;
    }
    
    public function getNextUndoneTrade(TradeRepository $tradeRepo) {

        if ($this->getState() == Sequence::OPEN){  
            $trades = $tradeRepo->findBySequence($this);
              foreach ($trades as $trade) {
                  // retourne la première mise 
                  if($trade->getSequenceState() != Trade::SEQSTATEDONE){
                    return $trade;
                  }
              }
              throw new \Exception('[getNextUndoneTrade] Erreur BDD Sequence ouverte avec rien a ratrapper !');
        }
        return NULL;
    }
    
        public function repartValueOnUndone(TradeRepository $tradeRepo, \UB\CoreBundle\Persister\TradePersister $tradePersister, $value) {

        if ($this->getState() == Sequence::OPEN) {
            $trades = $tradeRepo->findBySequence($this);
            $i = 0;
            foreach ($trades as $trade) {
                // retourne la première mise 
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE) {
                    if ($i > 0) {
                        echo 'SUM REPART -> ' . $value . '\n';
                        $trade->setAmount($trade->getAmount() + $value);
                        $tradePersister->persist($trade);
                    }
                    $i++;
                }
            }
        }
        return NULL;
    }
    
            public function eraseSmallTrade(TradeRepository $tradeRepo, \UB\CoreBundle\Persister\TradePersister $tradePersister) {

        if ($this->getState() == Sequence::OPEN) {
            $trades = $tradeRepo->findBySequence($this);
            $i = 0;
            foreach ($trades as $trade) {
                // retourne la première mise 
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE) {
                    if ($i > 0) {
                        $trade->setSequenceState(Trade::SEQSTATEDONE);
                        $tradePersister->persist($trade);
                        return $trade->getAmount();
                    }
                    $i++;
                }
            }
        }
        return 0;
    }

    public function checkHalfTradeAndFull(TradeRepository $tradeRepo, \UB\CoreBundle\Persister\TradePersister $tradePersister, $value) {

        if ($this->getState() == Sequence::OPEN){  
            $trades = $tradeRepo->findBySequence($this);
              foreach ($trades as $trade) {
                  // retourne la première mise 
                  if($trade->getSequenceState() == Trade::SEQSTATEHALF){
                    $trade->setAmount($trade->getAmount() + $value);
                    $trade->setSequenceState(Trade::SEQSTATEUNDONE);
                    $tradePersister->persist($trade);
                    return true;
                  }
              }
        }
        return false;
    }
    
        public function getNbLooseEvo(TradeRepository $tradeRepo) {

        if ($this->getState() == Sequence::OPEN) {
            $trades = $tradeRepo->findBySequence($this);
            $i = 0;
            foreach ($trades as $trade) {
                // retourne la première mise 
                if ($trade->getSequenceState() != Trade::SEQSTATEDONE) {
                    $i++;
                }
            }
        }
        echo "NBLOSSE TO DIVIDE ".($i-1)."\n";
        return $i - 1;
    }
    
    
    public function isFinished(TradeRepository $tradeRepo) {
        echo "------ is Finished ----- \n";
        $trades = $tradeRepo->findBySequence($this);
        foreach ($trades as $trade) {
            if ($trade->getSequenceState() != Trade::SEQSTATEDONE) {
              echo "------ NON TERMINEE ----- \n";  
                return false;
            }
        }
        $this->setTimeEnd(new \DateTime());
        $this->setState(Sequence::CLOSE);
        return true;
    }
    
    public function getSumWin() {
        $sum = 0;
        $trades = $this->getTrades();
        foreach ($trades as $trade) {
            // retourne la première mise 
            if ($trade->getState() == Trade::STATEWIN) {
                $sum += $trade->getAmountRes();
            }
        }
        return $sum;
    }
    
    public function getSumLoose() {
        $sum = 0;
        $trades = $this->getTrades();
        foreach ($trades as $trade) {
            // retourne la première mise 
            if ($trade->getState() == Trade::STATELOOSE && $trade->getSequenceState() != Trade::SEQSTATEDONE) {
                $sum += $trade->getAmount();
            }
        }
        return $sum;
    }
    
    public function initTradeWin(\UB\CoreBundle\Repository\TradeRepository $tradeRepo)
    {
        $trades = $tradeRepo->findBySequence($this);
        foreach ($trades as $trade) {
            $trade->win();
        }
    }
    
    
     /**
     * Set lengthTrinity
     *
     * @param integer $lengthTrinity
     *
     * @return Sequence
     */
    public function setLengthTrinity($lengthTrinity)
    {
        $this->lengthTrinity = $lengthTrinity;

        return $this;
    }

    /**
     * Get lengthTrinity
     *
     * @return int
     */
    public function getLengthTrinity()
    {
        return $this->lengthTrinity;
    }
 
     /**
     * Set position
     *
     * @param integer $position
     *
     * @return Sequence
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }
    
    

     /**
     * Set multiWin
     *
     * @param integer $multiWin
     *
     * @return Sequence
     */
    public function setMultiWin($multiWin)
    {
        $this->multiWin = $multiWin;

        return $this;
    }

    /**
     * Get multiWin
     *
     * @return int
     */
    public function getMultiWin()
    {
        return $this->multiWin;
    }   
     /**
     * Set multiLoose
     *
     * @param integer $multiLoose
     *
     * @return Sequence
     */
    public function setMultiLoose($multiLoose)
    {
        $this->multiLoose = $multiLoose;

        return $this;
    }

    /**
     * Get multiLoose
     *
     * @return int
     */
    public function getMultiLoose()
    {
        return $this->multiLoose;
    }   
     /**
     * Set sumLooseTR
     *
     * @param integer $sumLooseTR
     *
     * @return Sequence
     */
    public function setSumLooseTR($sumLooseTR)
    {
        $this->sumLooseTR = $sumLooseTR;

        return $this;
    }

    /**
     * Get sumLooseTR
     *
     * @return float
     */
    public function getSumLooseTR()
    {
        return $this->sumLooseTR;
    }
    
    /**
     * Set sumWinTR
     *
     * @param integer $sumWinTR
     *
     * @return Sequence
     */
    public function setSumWinTR($sumWinTR)
    {
        $this->sumWinTR = $sumWinTR;

        return $this;
    }

    /**
     * Get sumWinTR
     *
     * @return float
     */
    public function getSumWinTR()
    {
        return $this->sumWinTR;
    }
    
     /**
     * Set sumToRecup
     *
     * @param integer $sumToRecup
     *
     * @return Sequence
     */
    public function setSumToRecup($sumToRecup)
    {
        $this->sumToRecup = $sumToRecup;

        return $this;
    }

    /**
     * Get sumToRecup
     *
     * @return int
     */
    public function getSumToRecup()
    {
        return $this->sumToRecup;
    }
    
     /**
     * Set mise
     *
     * @param integer $mise
     *
     * @return Sequence
     */
    public function setMise($mise)
    {
        $this->mise = $mise;

        return $this;
    }

    /**
     * Get mise
     *
     * @return int
     */
    public function getMise()
    {
        return $this->mise;
    }
 
    public function getStatTenLastTrade(){
        $nbTRade = $this->getMultiLoose() + $this->getMultiWin();
        if ( $nbTRade >= 7){
            return ($this->getMultiWin()/($nbTRade/100));
        } else {
            return 50; // si pas suffisemant de trade je considère que je suis à 50%
        }
    }
    
    public function initMultiEveryTenTrade() {
        if ($this->getMultiLoose() + $this->getMultiWin() == 10) {
            $this->setMultiLoose(0);
            $this->setMultiWin(0);
        }
    }

}

