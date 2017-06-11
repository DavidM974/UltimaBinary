<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UB\CoreBundle\Entity\Trade;
/**
 * Cord
 *
 * @ORM\Table(name="cord")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\CordRepository")
 */
class Cord
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
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Trade", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $trade1;

    /**
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Trade", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $trade2;

    /**
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Trade", cascade={"persist"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $trade3;


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
     * Set trade1
     *
     * @param Trade
     *
     * @return Cord
     */
    public function setTrade1(Trade $trade1)
    {
        $this->trade1 = $trade1;

        return $this;
    }

    /**
     * Get trade1
     *
     * @return Trade
     */
    public function getTrade1()
    {
        return $this->trade1;
    }

    /**
     * Set trade2
     *
     * @param Trade
     *
     * @return Cord
     */
    public function setTrade2(Trade $trade2)
    {
        $this->trade2 = $trade2;

        return $this;
    }

    /**
     * Get trade2
     *
     * @return Trade
     */
    public function getTrade2()
    {
        return $this->trade2;
    }

    /**
     * Set trade3
     *
     * @param Trade
     *
     * @return Cord
     */
    public function setTrade3(Trade $trade3)
    {
        $this->trade3 = $trade3;

        return $this;
    }

    /**
     * Get trade3
     *
     * @return Trade
     */
    public function getTrade3()
    {
        return $this->trade3;
    }
}

