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
        $unit = ($trade->getSymbole()->getName() == 'R_75') ? 't' : 'm';
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
                            "duration_unit": "' . $unit . '",
                            "symbol": "' . $trade->getSymbole()->getName() . '"
                      }}');
    }

    function miseBaisse($conn, Trade $trade) {
        $unit = ($trade->getSymbole()->getName() == 'R_75') ? 't' : 'm';
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
                            "duration_unit": "' . $unit . '",
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
           $trade =  $this->tradeRepo->findOneBy(array('idBinary' => $transaction_id, 'state' => Trade::STATETRADE));
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
               //echo "trade non existant ou traiter ----------- \n";
           }
        }
        return $tabTread;
    }
    
    function SaveNewTrade($data) {
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
        $trade->setRate($this->calcRate($symbole,$data['echo_req']['parameters']['contract_type']));
        $this->tradePersister->persist($trade);

        return $trade;
    }
    // récupère le taux de la base utilisé pour ce trade
    public function calcRate($symbole, $contractType) {
        if ($contractType == Trade::TYPECALL) {
            return $symbole->getLastCallRate();
        } else {
            return $symbole->getLastPutRate();
        }
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

    function UpdateRate($conn, $contractType, $symbol) {
        $conn->send('
                    {
                        "proposal": 1,
                        "amount": "1",
                        "basis": "stake",
                        "contract_type": "'.$contractType.'",
                        "currency": "EUR",
                        "duration": "3",
                        "duration_unit": "m",
                        "symbol": "'.$symbol.'"
                      }');
    }
    
    function receiveUpdateRate($data)
    {
        $res = array();
        $rate = $data['proposal']['payout'] - 1;
        $res['rate'] = $rate; 
        $res['symbol'] = $data['echo_req']['symbol'];
        $res['contractType'] = $data['echo_req']['contract_type'];
        return $res;
    }


    
}
