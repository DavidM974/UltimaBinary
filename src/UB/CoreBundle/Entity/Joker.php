<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Joker
 *
 * @ORM\Table(name="joker")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\JokerRepository")
 */
class Joker
{
    const STATEUSE = 'USE';
    const STATEUNUSE = 'UNUSE';
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
     * @ORM\Column(name="state", type="string", columnDefinition="ENUM('USE', 'UNUSE')")
     */
    private $state;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_time", type="datetime")
     */
    private $dateTime;


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
     * Set state
     *
     * @param string $state
     *
     * @return Joker
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
     * Set dateTime
     *
     * @param \DateTime $dateTime
     *
     * @return Joker
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;

        return $this;
    }

    /**
     * Get dateTime
     *
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }
}

