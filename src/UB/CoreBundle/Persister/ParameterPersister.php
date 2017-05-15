<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of ParameterPersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Parameter;
use UB\CoreBundle\Repository\ParameterRepository;
use Doctrine\ORM\EntityManager;


class ParameterPersister 
{
    private $em;
    private $parameterRepo;
    
    public function __construct(EntityManager $em, ParameterRepository $parameterRepo) {
        
        $this->em = $em;
        $this->parameterRepo = $parameterRepo;
    }
    
    public function persist(Parameter $parameter)
    {
        $this->em->persist($parameter);
        $this->em->flush();
    }
}
