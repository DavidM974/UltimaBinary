<?php

namespace UB\CoreBundle\Entity;

use UB\CoreBundle\Entity\Symbole;
use Doctrine\ORM\Mapping as ORM;

/**
 * Signal
 *
 * @ORM\Table(name="trade_signal")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\TradeSignalRepository")
 */
class TradeSignal
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    private $startTime;

    /**
     * @var int
     *
     * @ORM\Column(name="duration", type="smallint")
     */
    private $duration;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_trade", type="boolean")
     */
    private $isTrade;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=true)
     */
    private $name;

    /**
    * @ORM\ManyToOne(targetEntity="UB\CoreBundle\Entity\Symbole")
    * @ORM\JoinColumn(nullable=true)
    */
    private $symbole;
    
    /**
     * @var string
     *
     * @ORM\Column(name="contract_type", type="string", columnDefinition="ENUM('CALL', 'PUT')")
     */
    private $contractType;
    
        /**
    * @ORM\ManyToOne(targetEntity="UB\CoreBundle\Entity\CategorySignal")
    * @ORM\JoinColumn(nullable=true)
    */
    private $categorySignal;
    
    
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
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Signal
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime
     */
    public function getStartTime()
    {
        return $this->startTime;
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
     * Set duration
     *
     * @param integer $duration
     *
     * @return Signal
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
     * Set isTrade
     *
     * @param boolean $isTrade
     *
     * @return Signal
     */
    public function setIsTrade($isTrade)
    {
        $this->isTrade = $isTrade;

        return $this;
    }

    /**
     * Get isTrade
     *
     * @return bool
     */
    public function getIsTrade()
    {
        return $this->isTrade;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Signal
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
}

