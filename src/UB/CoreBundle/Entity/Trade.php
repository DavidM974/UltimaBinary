<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Trade
 *
 * @ORM\Table(name="trade")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\TradeRepository")
 */
class Trade
{
    const SEQSTATEFIRST =       'FIRST';
    const SEQSTATEMARTING =     'MG';
    const SEQSTATELASTMARTING = 'LMG';
    const SEQSTATEUNDONE =      'UNDONE';
    const SEQSTATEDONE =        'DONE';
    const STATEWIN =            'WIN';
    const STATELOOSE =          'LOOSE';
    const STATETRADE =          'TRADE';
    const TYPECALL =            'CALL';
    const TYPEPUT =             'PUT';
    
    
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var float
     *
     * @ORM\Column(name="amount", type="float")
     */
    private $amount;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="smallint")
     */
    private $duration;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="signal_time", type="datetime")
     */
    private $signalTime;

    /**
     * @var string
     *
     * @ORM\Column(name="state", type="string", columnDefinition="ENUM('TRADE', 'WIN', 'LOOSE')")
     */
    private $state;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="sequence_state", type="string", columnDefinition="ENUM('FIRST','MG', 'LMG', 'UNDONE', 'DONE')")
     */
    private $sequenceState;

    /**
     * @var string
     *
     * @ORM\Column(name="contract_type", type="string", columnDefinition="ENUM('CALL', 'PUT')")
     */
    private $contractType;

    /**
     * @var bool
     *
     * @ORM\Column(name="result", type="boolean", nullable=true)
     */
    private $result;

    /**
     * @var float
     *
     * @ORM\Column(name="amount_res", type="float", nullable=true)
     */
    private $amountRes;

    /**
     * @var int
     *
     * @ORM\Column(name="idBinary", type="bigint", nullable=true)
     */
    private $idBinary;


    /**
    * @ORM\ManyToOne(targetEntity="UB\CoreBundle\Entity\Sequence", inversedBy = "trades")
    * @ORM\JoinColumn(nullable=true)
    */
    private $sequence;
    
    /**
    * @ORM\ManyToOne(targetEntity="UB\CoreBundle\Entity\Symbole")
    * @ORM\JoinColumn(nullable=true)
    */
    private $symbole;
    
    /**
    * @ORM\ManyToOne(targetEntity="UB\CoreBundle\Entity\CategorySignal")
    * @ORM\JoinColumn(nullable=true)
    */
    private $categorySignal;

    /**
     * @ORM\ManyToOne(targetEntity="UB\CoreBundle\Entity\Currency")
     * @ORM\JoinColumn(nullable=true)
     */
    private $currency;
    
    
    
    
    
    /**
     * Set sequence
     *
     * @param Sequence $sequence
     *
     * @return Trade
     */
    public function setSequence(Sequence $sequence)
    {
        $this->sequence = $sequence;
        // comme j'ajoute mon trade a une sequence j'augmente sa taille
        $this->sequence->addLength();
        return $this;
    }

    /**
     * Get sequence
     *
     * @return Sequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }
    
    /**
     * Set symbole
     *
     * @param Symbole $symbole
     *
     * @return Trade
     */
    public function setSymbole(Symbole $symbole)
    {
        $this->symbole = $symbole;

        return $this;
    }

    /**
     * Get symbole
     *
     * @return Symbole
     */
    public function getSymbole()
    {
        return $this->symbole;
    }

    /**
     * Set categorySignal
     *
     * @param Symbole $categorySignal
     *
     * @return Trade
     */
    public function setCategorySignal(CategorySignal $categorySignal)
    {
        $this->categorySignal = $categorySignal;

        return $this;
    }

    /**
     * Get categorySignal
     *
     * @return CategorySignal
     */
    public function getCategorySignal()
    {
        return $this->categorySignal;
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
     * Set amount
     *
     * @param float $amount
     *
     * @return Trade
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     *
     * @return Trade
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set signalTime
     *
     * @param \DateTime $signalTime
     *
     * @return Trade
     */
    public function setSignalTime($signalTime)
    {
        $this->signalTime = $signalTime;

        return $this;
    }

    /**
     * Get signalTime
     *
     * @return \DateTime
     */
    public function getSignalTime()
    {
        return $this->signalTime;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Trade
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
     * Set contractType
     *
     * @param string $contractType
     *
     * @return Trade
     */
    public function setContractType($contractType)
    {
        $this->contractType = $contractType;

        return $this;
    }

    /**
     * Get contractType
     *
     * @return string
     */
    public function getContractType()
    {
        return $this->contractType;
    }

    /**
     * Set sequenceState
     *
     * @param string $sequenceState
     *
     * @return Trade
     */
    public function setSequenceState($sequenceState)
    {
        $this->sequenceState = $sequenceState;

        return $this;
    }

    /**
     * Get sequenceState
     *
     * @return string
     */
    public function getSequenceState()
    {
        return $this->sequenceState;
    }
    
    /**
     * Set result
     *
     * @param boolean $result
     *
     * @return Trade
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * Get result
     *
     * @return bool
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * Set amountRes
     *
     * @param float $amountRes
     *
     * @return Trade
     */
    public function setAmountRes($amountRes)
    {
        $this->amountRes = $amountRes;

        return $this;
    }

    /**
     * Get amountRes
     *
     * @return float
     */
    public function getAmountRes()
    {
        return $this->amountRes;
    }

    /**
     * Set idBinary
     *
     * @param integer $idBinary
     *
     * @return Trade
     */
    public function setIdBinary($idBinary)
    {
        $this->idBinary = $idBinary;

        return $this;
    }

    /**
     * Get idBinary
     *
     * @return int
     */
    public function getIdBinary()
    {
        return $this->idBinary;
    }
    
     /**
     * Set currency
     *
     * @param Currency $currency
     *
     * @return Currency
     */
    public function setCurrency(Currency $currency = null)
    {
      $this->currency = $currency;
    }

    /**
     * Get currency
     *
     * @return Currency
     */
    public function getCurrency()
    {
      return $this->currency;
    }
    
    public function win() {
        $this->setSequenceState(Trade::SEQSTATEDONE);
        $this->setState(Trade::STATEWIN);
        return $this;
    }

}

