<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of CategorySignalPersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\CategorySignal;
use UB\CoreBundle\Repository\CategorySignalRepository;
use Doctrine\ORM\EntityManager;


class CategorySignalPersister 
{
    private $em;
    private $categorySignalRepo;
    
    public function __construct(EntityManager $em, CategorySignalRepository $categorySignalRepo) {
        
        $this->em = $em;
        $this->categorySignalRepo = $categorySignalRepo;
    }
    
    public function persist(CategorySignal $categorySignal)
    {
        $this->em->persist($categorySignal);
        $this->em->flush();
    }
}
