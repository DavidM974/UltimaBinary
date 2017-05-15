<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Symbole
 *
 * @ORM\Table(name="symbole")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\SymboleRepository")
 */
class Symbole
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var float
     *
     * @ORM\Column(name="last_call_rate", type="float", nullable=true)
     */
    private $lastCallRate;

    /**
     * @var float
     *
     * @ORM\Column(name="last_put_rate", type="float", nullable=true)
     */
    private $lastPutRate;

    /**
     * @var int
     *
     * @ORM\Column(name="minimum_time_trade", type="smallint", nullable=true)
     */
    private $minimumTimeTrade;

    /**
     * @var bool
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;


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
     * Set name
     *
     * @param string $name
     *
     * @return Symbole
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
     * Set lastCallRate
     *
     * @param float $lastCallRate
     *
     * @return Symbole
     */
    public function setLastCallRate($lastCallRate)
    {
        $this->lastCallRate = $lastCallRate;

        return $this;
    }

    /**
     * Get lastCallRate
     *
     * @return float
     */
    public function getLastCallRate()
    {
        return $this->lastCallRate;
    }

    /**
     * Set lastPutRate
     *
     * @param float $lastPutRate
     *
     * @return Symbole
     */
    public function setLastPutRate($lastPutRate)
    {
        $this->lastPutRate = $lastPutRate;

        return $this;
    }

    /**
     * Get lastPutRate
     *
     * @return float
     */
    public function getLastPutRate()
    {
        return $this->lastPutRate;
    }

    /**
     * Set minimumTimeTrade
     *
     * @param integer $minimumTimeTrade
     *
     * @return Symbole
     */
    public function setMinimumTimeTrade($minimumTimeTrade)
    {
        $this->minimumTimeTrade = $minimumTimeTrade;

        return $this;
    }

    /**
     * Get minimumTimeTrade
     *
     * @return int
     */
    public function getMinimumTimeTrade()
    {
        return $this->minimumTimeTrade;
    }

    /**
     * Set active
     *
     * @param boolean $active
     *
     * @return Symbole
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }
}

