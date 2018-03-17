<?php

namespace UB\CoreBundle\Persister;

/**
 * Description of SequencePersister
 *
 * @author David
 */

use UB\CoreBundle\Entity\Sequence;
use UB\CoreBundle\Repository\SequenceRepository;
use Doctrine\ORM\EntityManager;


class SequencePersister 
{
    private $em;
    private $sequenceRepo;
    
    public function __construct(EntityManager $em, SequenceRepository $sequenceRepo) {
        
        $this->em = $em;
        $this->sequenceRepo = $sequenceRepo;
    }
    
    public function newSequence($LengthTrinity, $newSumLoose, $balanceStart, $isMaster, $symbole) {
        $sequence = new Sequence();
        $sequence->setLength(0);
        $sequence->setState(Sequence::OPEN);
        $sequence->setMode(Sequence::REVERSE);
        $sequence->setPosition(0);
        $sequence->setMultiWin(0);
        $sequence->setMultiLoose(0);
        $sequence->setLastOFA(0);
        $sequence->setMise(0);
        $sequence->setLengthTrinity($LengthTrinity);
        $sequence->setSumLooseTR($newSumLoose);
        $sequence->setSumWinTR(0);
        $sequence->setTimeStart(new \DateTime());
        $sequence->setBalanceStart($balanceStart);
        $sequence->setIsMaster($isMaster);
        $sequence->setSymbole($symbole);
        $this->persist($sequence);
        return $sequence;
    }
    
    public function persist(Sequence $sequence)
    {
        $this->em->persist($sequence);
        $this->em->flush();
    }
}
