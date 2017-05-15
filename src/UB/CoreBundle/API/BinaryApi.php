<?php

namespace UB\CoreBundle\API;

use UB\CoreBundle\Entity\Trade;
use UB\CoreBundle\Repository\TradeRepository;
use UB\CoreBundle\Repository\SymboleRepository;
use UB\CoreBundle\Repository\CurrencyRepository;
use UB\CoreBundle\Repository\ParameterRepository;
use UB\CoreBundle\Persister\TradePersister;
use UB\CoreBundle\Persister\ParameterPersister;

/**
 * Description of BinaryApi
 *
 * @author David
 */
class BinaryApi implements ApiInterface {
    
    private $tradePersister;
    private $tradeRepo;
    private $symboleRepo;
    private $currencyRepo;
    private $parameterRepo;
    private $parameterPersister;
    
            
    function __construct(TradePersister $tradePersister, TradeRepository $tradeRepo, SymboleRepository $symboleRepo, CurrencyRepository $currencyRepo, ParameterRepository $parameterRepo, ParameterPersister $parameterPersister) {
        $this->tradePersister = $tradePersister;
        $this->tradeRepo = $tradeRepo;
        $this->currencyRepo = $currencyRepo;
        $this->symboleRepo = $symboleRepo;
        $this->parameterRepo = $parameterRepo;
        $this->parameterPersister = $parameterPersister;
    }
                function miseHausse($conn, Trade $trade) {
        $conn->send('
                    {
                      "buy": "1",
                      "price": ' . $trade->getAmount() . ',
                      "parameters": {
                            "amount": "' . $trade->getAmount() . '",
                            "basis": "stake",
                            "contract_type": "CALL",
                            "currency": "' . $trade->getCurrency()->getName() . '",
                            "duration": "' . $trade->getDuration() . '",
                            "duration_unit": "m",
                            "symbol": "' . $trade->getSymbole()->getName() . '"
                      }}');
    }

    function miseBaisse($conn, Trade $trade) {
        $conn->send('
                    {
                      "buy": "1",
                      "price": ' . $trade->getAmount() . ',
                      "parameters": {
                            "amount": "' . $trade->getAmount() . '",
                            "basis": "stake",
                            "contract_type": "PUT",
                            "currency": "' . $trade->getCurrency()->getName() . '",
                            "duration": "' . $trade->getDuration() . '",
                            "duration_unit": "m",
                            "symbol": "' . $trade->getSymbole()->getName() . '"
                      }}');
    }

    //demande les X dernier resultat
    function askLastResult($conn, $nbResult = 10) {
        $date = new \DateTime();
        $date->modify('-3 day');
        $conn->send('
                    {
                    "profit_table": 1,
                    "date_from": "' . $date->format('Y-m-d') . '",
                    "date_to": "' . date('Y-m-d') . '",
                    "limit": '.$nbResult.'
                    }');
    }

    function getLastResult($data) {
        //crée le fichier pour envoyer au MT4 2 champs transaction_id, res
        $tabProfit = $data['profit_table']['transactions'];
        $tabTread = array();
        foreach ($tabProfit as $tab) {
            $transaction_id = $tab['transaction_id'];
            $res = $tab['sell_price'];
            $mise = $tab['buy_price'];
            $payout = $tab['payout'];
            $taux = floor(($payout - $mise) / ($mise / 100));

            // checker si trade existant sinon j'enregistre
           $trade =  $this->tradeRepo->findOneBy(array('idBinary' => $transaction_id));
           if ($trade != NULL) {
               
               if ($res > 0) {
                $trade->setAmountRes($res - $trade->getAmount());
                $trade->setResult(1);
                $trade->setState(Trade::STATEWIN);
               } else {
                   $trade->setResult(0);
                   $trade->setAmountRes(0);
                    $trade->setState(Trade::STATELOOSE);
               }
               $tabTread[] = $trade;
               $this->tradePersister->persist($trade);
           } else {
               //todo créer le trade 
           }
        }
        return $tabTread;
    }
    
    function SaveNewTrade($data) {
        //crée le fichier pour envoyer au MT4 2 champs transaction_id, mise
        
        
                $symbole = $this->symboleRepo->findOneBy(array('name' => $data['echo_req']['parameters']['symbol']));
                $currency = $this->currencyRepo->findOneBy(array('name' => $data['echo_req']['parameters']['currency']));
                
                $trade = new Trade();
                $trade->setAmount($data['echo_req']['parameters']['amount']);
                $trade->setSymbole($symbole);
                $trade->setDuration($data['echo_req']['parameters']['duration']);
                $trade->setCurrency($currency);
                $trade->setContractType($data['echo_req']['parameters']['contract_type']);
                $trade->setState(Trade::STATETRADE);
                $trade->setIdBinary($data['buy']['transaction_id']);
                $trade->setSignalTime(new \DateTime());
                $this->tradePersister->persist($trade);

                return $trade;
    }

    //ecris le montant dans un fichier
    function sendBalance($conn) {
        $conn->send('{"balance": 1}');
    }
    
    function receivedBalance($data) {
        $parameter = $this->parameterRepo->findOneById(\UB\CoreBundle\Entity\Parameter::DEFAULT_ID);
        $parameter->setBalance($data['balance']['balance']);
        $this->parameterPersister->persist($parameter);
    }
    
    function receivedAuthorize($data){
        $parameter = $this->parameterRepo->findOneById(\UB\CoreBundle\Entity\Parameter::DEFAULT_ID);
        $parameter->setBalance($data['authorize']['balance']);
        $this->parameterPersister->persist($parameter);
    }

    function sendPing($conn) {
        $conn->send('{"ping": 1} ');
    }

}
