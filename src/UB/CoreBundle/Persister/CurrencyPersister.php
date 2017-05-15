<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of CurrencyPersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Currency;
use UB\CoreBundle\Repository\CurrencyRepository;
use Doctrine\ORM\EntityManager;


class CurrencyPersister 
{
    private $em;
    private $currencyRepo;
    
    public function __construct(EntityManager $em, CurrencyRepository $currencyRepo) {
        
        $this->em = $em;
        $this->currencyRepo = $currencyRepo;
    }
    
    public function persist(Currency $currency)
    {
        $this->em->persist($currency);
        $this->em->flush();
    }
}
