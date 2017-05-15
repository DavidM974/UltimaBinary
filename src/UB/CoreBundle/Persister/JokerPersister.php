<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of TradePersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Joker;
use UB\CoreBundle\Repository\JokerRepository;
use Doctrine\ORM\EntityManager;


class JokerPersister 
{
    private $em;
    private $jokerRepo;
    
    public function __construct(EntityManager $em, JokerRepository $jokerRepo) {
        
        $this->em = $em;
        $this->jokerRepo = $jokerRepo;
    }
    
    public function persist(Joker $joker)
    {
        $this->em->persist($joker);
        $this->em->flush();
    }
    
    public function newJokerUnUse() {
        $joker = new Joker();
        $joker->setState(Joker::STATEUNUSE);
        $joker->setDateTime(new \DateTime());
        $this->persist($joker);
        return $joker;
    }
}
