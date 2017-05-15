<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of TradePersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\StaticWinJoker;
use UB\CoreBundle\Repository\StaticWinJokerRepository;
use Doctrine\ORM\EntityManager;


class StaticWinJokerPersister 
{
    private $em;
    private $staticWinJokerRepo;
    
    public function __construct(EntityManager $em, StaticWinJokerRepository $staticWinJokerRepo) {
        
        $this->em = $em;
        $this->staticWinJokerRepo = $staticWinJokerRepo;
    }
    
    public function persist(StaticWinJoker $staticWinJoker)
    {
        $this->em->persist($staticWinJoker);
        $this->em->flush();
    }
}
