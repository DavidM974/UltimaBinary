<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

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
    const JOKERNOTUSE = 'NOTUSE';
    const JOKERUSE = 'USE';
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
     * @ORM\Column(name="joker", type="string" , columnDefinition="ENUM('NOTUSE', 'USE')")
     */
    private $joker;
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
     * Set joker
     *
     * @param string $joker
     *
     * @return Sequence
     */
    public function setJoker($joker)
    {
        $this->joker = $joker;

        return $this;
    }

    /**
     * Get joker
     *
     * @return string
     */
    public function getJoker()
    {
        return $this->joker;
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
    public function getNextAmountSequence(\UB\CoreBundle\Repository\TradeRepository $tradeRepo) {
        echo "getNextAmountSequence \n";

          $trades = $tradeRepo->findBySequence($this);

          foreach ($trades as $trade) {
              // retourne la première mise 
              echo 'id trade :'. $trade->getId()."\n";
              if($trade->getSequenceState() != Trade::SEQSTATEDONE){
                return $trade->getAmount();
              }
          }
          //si pas de mise pour cette séquence
          
        return 0;
    }
    
    
            // récupère la prochaine mise de la séquence non rattrapé
    public function getNextAmountSequenceMg(\UB\CoreBundle\Repository\TradeRepository $tradeRepo) {
        echo "getNextAmountSequence MG\n";

          $trades = $tradeRepo->findBySequence($this);
          $sumLoose = 0;
          foreach ($trades as $trade) {
              // retourne la première mise 
              echo 'id trade :'. $trade->getId()."\n";
              if($trade->getSequenceState() == Trade::SEQSTATEFIRST || $trade->getSequenceState() == Trade::SEQSTATEMARTING){
                $sumLoose += $trade->getAmount();
              }
          }
          
          if ($sumLoose > 0) {echo "Recupere les trades avant LMG\n"; return $sumLoose; }
          
          foreach ($trades as $trade) {
              // retourne la première mise 
              echo 'id trade :'. $trade->getId()."\n";
              if($trade->getSequenceState() == Trade::SEQSTATEUNDONE || $trade->getSequenceState() == Trade::SEQSTATELASTMARTING){
                return $trade->getAmount();
              }
          }
          //si pas de mise pour cette séquence
          
        return 0;
    }
    
    public function getLastMgSequence(\UB\CoreBundle\Repository\TradeRepository $tradeRepo) {

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
    
    public function getNextUndoneTrade(\UB\CoreBundle\Repository\TradeRepository $tradeRepo) {

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
    
    public function isFinished() {
        foreach ($this->trades as $trade) {
            if ($trade->getSequenceState() != Trade::SEQSTATEDONE) {
                return false;
            }
        }
        $this->setState(Sequence::CLOSE);
        return true;
    }
    
    public function getSumWin() {
        $sum = 0;
        $trades = $this->getTrades();
        foreach ($trades as $trade) {
            // retourne la première mise 
            if ($trade->getState() != Trade::STATEWIN) {
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
            if ($trade->getState() != Trade::STATELOOSE) {
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

}

