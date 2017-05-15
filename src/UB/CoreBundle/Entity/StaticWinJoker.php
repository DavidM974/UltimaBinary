<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * StaticWinJoker
 *
 * @ORM\Table(name="static_win_joker")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\StaticWinJokerRepository")
 */
class StaticWinJoker
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
     * @var int
     *
     * @ORM\Column(name="consecutive_win", type="integer", nullable=true)
     */
    private $consecutiveWin;


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
     * Set consecutiveWin
     *
     * @param integer $consecutiveWin
     *
     * @return StaticWinJoker
     */
    public function setConsecutiveWin($consecutiveWin)
    {
        $this->consecutiveWin = $consecutiveWin;

        return $this;
    }

    /**
     * Get consecutiveWin
     *
     * @return int
     */
    public function getConsecutiveWin()
    {
        return $this->consecutiveWin;
    }
    

    public function addWin()
    {
        $this->setConsecutiveWin($this->getConsecutiveWin() + 1);
        return $this;
    }
    
    public function resetWin() {
        $this->setConsecutiveWin(0);
        return;
    }
}

