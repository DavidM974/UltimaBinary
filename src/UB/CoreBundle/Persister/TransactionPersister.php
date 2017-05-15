<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of TransactionPersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Transaction;
use UB\CoreBundle\Repository\TransactionRepository;
use Doctrine\ORM\EntityManager;


class TransactionPersister 
{
    private $em;
    private $transactionRepo;
    
    public function __construct(EntityManager $em, TransactionRepository $transactionRepo) {
        
        $this->em = $em;
        $this->transactionRepo = $transactionRepo;
    }
    
    public function persist(Transaction $transaction)
    {
        $this->em->persist($transaction);
        $this->em->flush();
    }
}
