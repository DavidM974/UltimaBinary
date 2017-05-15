<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of SymbolePersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Symbole;
use UB\CoreBundle\Repository\SymboleRepository;
use Doctrine\ORM\EntityManager;


class SymbolePersister 
{
    private $em;
    private $symboleRepo;
    
    public function __construct(EntityManager $em, SymboleRepository $symboleRepo) {
        
        $this->em = $em;
        $this->symboleRepo = $symboleRepo;
    }
    
    public function persist(Symbole $symbole)
    {
        $this->em->persist($symbole);
        $this->em->flush();
    }
}
