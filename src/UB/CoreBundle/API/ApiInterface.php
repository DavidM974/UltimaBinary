<?php

namespace UB\CoreBundle\API;
use UB\CoreBundle\Entity\Trade;
/**
 *
 * @author David
 */
interface ApiInterface {
   
		function miseHausse($conn, Trade $trade);
		//frxUSDJPY
		function miseBaisse($conn,Trade $trade);
                //demande les X dernier resultat
		function askLastResult($conn, $nbResult = 10);
                
                function getLastResult($data, \UB\CoreBundle\Entity\Symbole $symbole);
                
                function SaveNewTrade($data);
		//ecris le montant dans un fichier
		function sendBalance($conn);
                
                function sendPing($conn);
}
