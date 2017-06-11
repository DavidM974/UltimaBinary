<?php

namespace UB\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use UB\CoreBundle\Entity\Cord;
use Doctrine\Common\Collections\ArrayCollection;
use UB\CoreBundle\Entity\Sequence;
/**
 * Rope
 *
 * @ORM\Table(name="rope")
 * @ORM\Entity(repositoryClass="UB\CoreBundle\Repository\RopeRepository")
 */
class Rope
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
     * @ORM\Column(name="mode", type="string", columnDefinition="ENUM('JOKER','CLOSEFORALL', 'TRINITY')")
     */
    private $mode;

    /**
    * @ORM\OneToMany(targetEntity="UB\CoreBundle\Entity\Sequence", mappedBy="rope", fetch="EAGER")
    * @ORM\OrderBy({"timeStart" = "ASC"})
    */
    private $sequences;

    /**
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Cord", cascade={"persist"})
     * 
     */
    private $cord1;

    /**
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Cord", cascade={"persist"})
     */
    private $cord2;

    /**
     * @ORM\OneToOne(targetEntity="UB\CoreBundle\Entity\Cord", cascade={"persist"})
     */
    private $cord3;

    public function __construct()
    {
      $this->sequences = new ArrayCollection();
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
     * Set mode
     *
     * @param string $mode
     *
     * @return Rope
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

    public function addSequence(Sequence $sequence)
    {
      $this->sequences[] = $sequence;
      $this->addLength();
    }

    public function removeSequence(Sequence $sequence)
    {
      $this->sequences->removeElement($sequence);
    }

    public function getSequences()
    {
      return $this->sequences;
    }

    /**
     * Set cord1
     *
     * @param Cord
     *
     * @return Rope
     */
    public function setCord1(Cord $cord1)
    {
        $this->cord1 = $cord1;

        return $this;
    }

    /**
     * Get cord1
     *
     * @return Cord
     */
    public function getCord1()
    {
        return $this->cord1;
    }

    /**
     * Set cord2
     *
     * @param Cord
     *
     * @return Rope
     */
    public function setCord2(Cord $cord2)
    {
        $this->cord2 = $cord2;

        return $this;
    }

    /**
     * Get cord2
     *
     * @return Cord
     */
    public function getCord2()
    {
        return $this->cord2;
    }

    /**
     * Set cord3
     *
     * @param Cord
     *
     * @return Rope
     */
    public function setCord3(Cord $cord3)
    {
        $this->cord3 = $cord3;

        return $this;
    }

    /**
     * Get cord3
     *
     * @return Cord
     */
    public function getCord3()
    {
        return $this->cord3;
    }
}

