<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Parameter
 *
 * @ORM\Table(name="parameter")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\ParameterRepository")
 */
class Parameter
{
    const DEFAULT_ID = 1;
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
     * @ORM\Column(name="defaultRate", type="float")
     */
    private $defaultRate;

    /**
     * @var int
     *
     * @ORM\Column(name="martingaleSize", type="integer")
     */
    private $martingaleSize;

    /**
     * @var int
     *
     * @ORM\Column(name="jokerSize", type="integer")
     */
    private $jokerSize;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_time", type="datetime")
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="datetime")
     */
    private $endTime;

    /**
     * @var int
     *
     * @ORM\Column(name="dayActive", type="smallint")
     */
    private $dayActive;

    /**
     * @var bool
     *
     * @ORM\Column(name="mode", type="boolean")
     */
    private $mode;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="is_active_m1", type="boolean")
     */
    private $isActiveM1;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_active_m5", type="boolean")
     */
    private $isActiveM5;
    
    /**
     * @var bool
     *
     * @ORM\Column(name="state", type="boolean")
     */
    private $state;

    /**
     * @var int
     *
     * @ORM\Column(name="securitySequence", type="integer", nullable=true)
     */
    private $securitySequence;

    /**
     * @var int
     *
     * @ORM\Column(name="maxParallelSequence", type="integer", nullable=true)
     */
    private $maxParallelSequence;

    /**
     * @var int
     *
     * @ORM\Column(name="probaJokerOn", type="boolean", nullable=true)
     */
    private $probaJokerOn;

    /**
     * @var int
     *
     * @ORM\Column(name="jokerConsecutive", type="boolean", nullable=true)
     */
    private $jokerConsecutive;
    
    /**
     * @var float
     *
     * @ORM\Column(name="balance", type="float")
     */
    private $balance;

    /**
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Currency", cascade={"persist"})
     */
    private $currency;

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
     * Set defaultRate
     *
     * @param float $defaultRate
     *
     * @return Parameter
     */
    public function setDefaultRate($defaultRate)
    {
        $this->defaultRate = $defaultRate;

        return $this;
    }

    /**
     * Get defaultRate
     *
     * @return float
     */
    public function getDefaultRate()
    {
        return $this->defaultRate;
    }

    /**
     * Set martingaleSize
     *
     * @param integer $martingaleSize
     *
     * @return Parameter
     */
    public function setMartingaleSize($martingaleSize)
    {
        $this->martingaleSize = $martingaleSize;

        return $this;
    }

    /**
     * Get martingaleSize
     *
     * @return int
     */
    public function getMartingaleSize()
    {
        return $this->martingaleSize;
    }

    /**
     * Set jokerSize
     *
     * @param integer $jokerSize
     *
     * @return Parameter
     */
    public function setJokerSize($jokerSize)
    {
        $this->jokerSize = $jokerSize;

        return $this;
    }

    /**
     * Get jokerSize
     *
     * @return int
     */
    public function getJokerSize()
    {
        return $this->jokerSize;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     *
     * @return Parameter
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
     * Set endTime
     *
     * @param \DateTime $endTime
     *
     * @return Parameter
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set isActiveM1
     *
     * @param boolean $isActiveM1
     *
     * @return Parameter
     */
    public function setIsActiveM1($isActiveM1)
    {
        $this->isActiveM1 = $isActiveM1;

        return $this;
    }

    /**
     * Get isActiveM1
     *
     * @return bool
     */
    public function getIsActiveM1()
    {
        return $this->isActiveM1;
    }

    /**
     * Set isActiveM5
     *
     * @param boolean $isActiveM5
     *
     * @return Parameter
     */
    public function setIsActiveM5($isActiveM5)
    {
        $this->isActiveM5 = $isActiveM5;

        return $this;
    }

    /**
     * Get isActiveM5
     *
     * @return bool
     */
    public function getIsActiveM5()
    {
        return $this->isActiveM5;
    }
    
    /**
     * Set dayActive
     *
     * @param integer $dayActive
     *
     * @return Parameter
     */
    public function setDayActive($dayActive)
    {
        $this->dayActive = $dayActive;

        return $this;
    }

    /**
     * Get dayActive
     *
     * @return int
     */
    public function getDayActive()
    {
        return $this->dayActive;
    }
    
    /**
     * Set mode
     *
     * @param boolean $mode
     *
     * @return Parameter
     */
    public function setMode($mode)
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * Get mode
     *
     * @return bool
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Set state
     *
     * @param boolean $state
     *
     * @return Parameter
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return bool
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set securitySequence
     *
     * @param integer $securitySequence
     *
     * @return Parameter
     */
    public function setSecuritySequence($securitySequence)
    {
        $this->securitySequence = $securitySequence;

        return $this;
    }

    /**
     * Get securitySequence
     *
     * @return int
     */
    public function getSecuritySequence()
    {
        return $this->securitySequence;
    }

    /**
     * Set maxParallelSequence
     *
     * @param integer $maxParallelSequence
     *
     * @return Parameter
     */
    public function setMaxParallelSequence($maxParallelSequence)
    {
        $this->maxParallelSequence = $maxParallelSequence;

        return $this;
    }

    /**
     * Get maxParallelSequence
     *
     * @return int
     */
    public function getMaxParallelSequence()
    {
        return $this->maxParallelSequence;
    }

    /**
     * Set probaJokerOn
     *
     * @param integer $probaJokerOn
     *
     * @return Parameter
     */
    public function setProbaJokerOn($probaJokerOn)
    {
        $this->probaJokerOn = $probaJokerOn;

        return $this;
    }

    /**
     * Get probaJokerOn
     *
     * @return int
     */
    public function getProbaJokerOn()
    {
        return $this->probaJokerOn;
    }

    /**
     * Set jokerConsecutive
     *
     * @param integer $jokerConsecutive
     *
     * @return Parameter
     */
    public function setJokerConsecutive($jokerConsecutive)
    {
        $this->jokerConsecutive = $jokerConsecutive;

        return $this;
    }

    /**
     * Get jokerConsecutive
     *
     * @return int
     */
    public function getJokerConsecutive()
    {
        return $this->jokerConsecutive;
    }
    
    /**
     * Set balance
     *
     * @param float $balance
     *
     * @return Parameter
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance
     *
     * @return float
     */
    public function getBalance()
    {
        return $this->balance;
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
    
    public function isMgActive() {
        if ($this->getMartingaleSize() > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}

