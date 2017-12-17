<?php

namespace UB\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use UB\CoreBundle\Repository\TradeRepository;
use UB\CoreBundle\Entity\Trade;

class DefaultController extends Controller {

    private $consecWinSeq1 = 0;
    private $consecLooseSeq1 = 0;
    private $consecWinSeq2 = 0;
    private $consecLooseSeq2 = 0;
    private $account = 1000;
    private $originalPalier = 5;
    private $palier = 5;
    private $miseInit = 1;
    private $taux = 0.94;
    private $plusBas = 0;
    private $talent = 0;

    public function indexAction() {
          $listTrades = $this->getDoctrine()
          ->getManager()
          ->getRepository('UBCoreBundle:Trade')
->findAll();         
// ->getLastDaysTrade(7);
          
         
        $tabRes = array();
        $this->consecWinSeq1 = 0;
        $this->consecLooseSeq1 = 0;
        $this->consecWinSeq2 = 0;
        $this->consecLooseSeq2 = 0;
        $this->account = 1000;
        $this->palier = 5;
        $this->miseInit = 1;
        $this->taux = 0.94;
        $this->plusBas = $this->account;
        $this->talent = $this->calcTalent();
        for ($i = 1; $i <= $this->palier; $i++) {
            $tabRes[$i] = 0;
        }
        foreach ($listTrades as $trade) {
        //for ($nb = 0; $nb < 17000; $nb++) {

            if (mt_rand(0, 99) < 50) {
                $resultat = 0;
            } else {
                $resultat = 1;
            }
             if ($trade->getState() == 'WIN') {
            //if ($resultat) {
                $this->updateTalentData($this->consecWinSeq1, $this->consecWinSeq2, $this->consecLooseSeq1, $this->consecLooseSeq2, $tabRes);
            } elseif ($trade->getState() == 'LOOSE') {
            //elseif (!$resultat) {
                $this->updateTalentData($this->consecWinSeq2, $this->consecWinSeq1, $this->consecLooseSeq2, $this->consecLooseSeq1, $tabRes);
            }
        }
        return $this->render('UBPlatformBundle:Default:index.html.twig', array(
                    'account' => $this->account,
                    'tabRes' => $tabRes,
                    'plusBas' => $this->plusBas,
                    'talent' => $this->talent));
    }

    public function calcTalent() {
        $res = $this->miseInit;
        $newRes = 0;
        for ($i = 0; $i < $this->palier; $i++) {
            $newRes += ($res * $this->taux);
            $res += $newRes;
            $newRes = 0;
        }
        return $res;
    }

    public function updateTalentData(&$winSequence1, &$winSequence2, &$looseSequence1, &$looseSequence2, &$tabRes) {
        if ($winSequence2 == 0) {//||  $consecWinSeq1 == ($palier -1)
            $winSequence1++;
            if ($winSequence1 == 1) {
                $this->account -= $this->miseInit;
                if ($this->account < $this->plusBas) {
                    $this->plusBas = $this->account;
                }
            }
        }
        if ($winSequence2 > 0) {
            $tabRes[$winSequence2] += 1;
            if ($winSequence2 == $this->originalPalier - 1){
                $this->palier -= 1;
            } else {
                $this->palier = $this->originalPalier;
            }
        }

        $looseSequence1 = 0;
        if ($winSequence1 == $this->palier) {
            if ($this->palier != $this->originalPalier) {
                $this->palier = $this->originalPalier;
            }
            $this->account += $this->talent;
            if ($this->account < $this->plusBas) {
                $this->plusBas = $this->account;
            }
            $tabRes[$winSequence1] += 1;
            $winSequence1 = 0;
            $looseSequence2 = 0;
        }
        $looseSequence2++;
        if ($looseSequence2 == 1 && $winSequence2 == 0) {// prend pas de trade a la fin d'un palier non atteint
            $this->account -= $this->miseInit;

            if ($this->account < $this->plusBas) {
                $this->plusBas = $this->account;
            }
        }
        $winSequence2 = 0;
    }

}
