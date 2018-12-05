<?php

namespace UB\CoreBundle\Repository;

use \UB\CoreBundle\Entity\Sequence;
/**
 * SequenceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SequenceRepository extends \Doctrine\ORM\EntityRepository
{
    // Récupérer la dernière séquence ouverte
    public function getLastOpenSequence() {
        $sequence =  $this->findOneBy(
            array ('state'=>'OPEN'),
            array ('timeStart'=>'ASC', 'length' => 'desc')
            );
        return $sequence;

    }
    
        public function getLastOpenSequenceSens($isMaster, $symbole) {

        $sequence =  $this->findOneBy(
            array ('state'=>'OPEN','isMaster' => "$isMaster", 'symbole' => $symbole),
            array ('timeStart'=>'DESC')
            );
        return $sequence;

    }
    
    public function getLastCloseSequenceSensLength($isMaster, $symbole) {

        $sequence =  $this->findOneBy(
            array ('state'=>'CLOSE','isMaster' => "$isMaster", 'symbole' => $symbole),
            array ('timeStart'=>'DESC')
            );
        //verifi si c'est bien une sequence win et pas de loose cloture par les win
        if ($sequence != NULL && $sequence->getMultiWin() > $sequence->getMultiLoose())
        {
            return $sequence->getLength();
        } else {
            return 0;
        }
        

    }
    // todo filtrer pas de trade en cours
    public function getOpenSequence() {
        $sequences =  $this->findBy(
            array ('state'=>'OPEN'),
            array ('timeStart'=>'ASC', 'length' => 'desc')
            );
        return $sequences;

    }
    
    public function checkSignalRandomTrade($idSignal) {
        $listSgnal = Array(5);
        foreach ($listSgnal as $id) {
            if ($idSignal == $id) {
                return TRUE;
            }
        }
        return FALSE;
    }
    
        public function checkSymboleRandomTrade($idSymbole) {
        $listSgnal = Array(3);
        foreach ($listSgnal as $id) {
            if ($idSymbole == $id) {
                return TRUE;
            }
        }
        return FALSE;
    }
    
  
    
    
    public function getOpenSequenceNotTrading($symbole, $isMaster) {
        $subqb = $this->createQueryBuilder('s');
        $subQuery = $subqb->select('s.id')
                ->innerJoin('s.trades', 'tr')
                ->where('tr.state = :state    ')
                ->setParameter('state', 'TRADE')
                ->andWhere('tr.symbole = :symbole')
                ->setParameter('symbole', $symbole)
                ->getQuery()
                ->getArrayResult()
        ;
        $qb = $this->createQueryBuilder('s');
        if (empty($subQuery)) {
            $subQuery = array(0);
        }
        $qb
                ->select('s')
                ->where($qb->expr()->notIn('s.id', ':subQuery'))
                ->setParameter('subQuery', $subQuery)
                ->andWhere('s.state = :state')
                ->setParameter('state', 'OPEN')
                ->andWhere('s.isMaster = :isMaster')
                ->setParameter('isMaster', $isMaster)
                ->andWhere('s.symbole = :symbole')
                ->setParameter('symbole', $symbole)
                ;
               $query = $qb->getQuery();
        return $query->getResult();
    }
    public function getOpenSequenceTrading($symbole, $isMaster) {
        $subqb = $this->createQueryBuilder('s');
        $subQuery = $subqb->select('s.id')
                ->innerJoin('s.trades', 'tr')
                ->where('tr.state = :state    ')
                ->setParameter('state', 'TRADE')
                ->andWhere('tr.symbole = :symbole')
                ->setParameter('symbole', $symbole)
                ->getQuery()
                ->getArrayResult()
        ;
        $qb = $this->createQueryBuilder('s');
        if (empty($subQuery)) {
            $subQuery = array(0);
        }
        $qb
                ->select('s')
                ->where($qb->expr()->in('s.id', ':subQuery'))
                ->setParameter('subQuery', $subQuery)
                ->andWhere('s.state = :state')
                ->setParameter('state', 'OPEN')
                ->andWhere('s.isMaster = :isMaster')
                ->setParameter('isMaster', $isMaster)
                ->andWhere('s.symbole = :symbole')
                ->setParameter('symbole', $symbole)
                ;
               $query = $qb->getQuery();
        return $query->getOneOrNullResult();
    }
    public function isSequenceOpen() {
        $sequences = $this->getOpenSequence();
        if (empty($sequences)) {
            return false;
        }
        return TRUE;
    }

}
