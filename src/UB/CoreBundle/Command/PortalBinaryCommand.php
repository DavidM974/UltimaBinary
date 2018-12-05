<?php

namespace UB\CoreBundle\Command;
/**
 * Description of ControllerPortalBinary
 *
 * @author David
 */
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use React\EventLoop\Factory;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Devristo\Phpws\Client;

class PortalBinaryCommand extends ContainerAwareCommand
{
    private $loop;
    private $clientector;
    private $app;
    private $api;
    private $ubAlgo;
    private $tradeSignalPersister;
    private $currency;
    private $symbole;
    private $logger;
    private $writer;
    private $client;
    // const APIKEY='hbMdhGGQErEeCXN';
   //const APIKEY='oGrJBdcWE3VPIOQ'; //demo elimina
       //const APIKEY='mBH6b0KVabovDHQ'; // demo 2
       const APIKEY= 'IEMhkpxXyMy2eWN';
    //const APIKEY = 'ZG9LgGWjNVxxmNi';
    
    
    function __construct(){
        parent::__construct();
         $this->loop = Factory::create();
         $this->logger = new Logger();
         $this->writer = new Stream("php://output");
         $this->logger->addWriter($this->writer);
    }
    
    
    
    public function mainCore($apiKey, $loop, $logger) {

// "app_id": "3008" 3020
        $client = new Client\WebSocket("wss://ws.binaryws.com/websockets/v3?app_id=3008", $loop, $logger);

$client->on("request", function($headers) use ($logger){
    $logger->notice("Request object created!");
});

$client->on("handshake", function() use ($logger) {
    $logger->notice("Handshake received!");
});

$client->on("connect", function() use ($logger, $client, $apiKey){
    $logger->notice("Connected!");
    $client->send('{"authorize": "' . $apiKey . '"}');
});
$client->on("close", function() use ($logger, $client, $apiKey){
    $logger->notice("Close!");
    $client->send('{"authorize": "' . $apiKey . '"}');
});

$client->on("message", function($message) use ($client, $logger){
    $logger->notice("Got message: ".$message->getData());
    
                    $json = json_decode($message->getData(), true);
                if (isset($json['buy'])) {
                $parameterPersister = $this->getContainer()->get('ub_core.parameter_persister');
                $parameterRepo = $this->getContainer()->get('parameter_repo');
                $parameter = $parameterRepo->findOneById(1);
                $parameter->setSecurityTrade(0);
                $parameterPersister->persist($parameter);
                // api save new trade
                    $trade = $this->api->saveNewTrade($json);
                    $this->ubAlgo->setLocked(1);
                    if ($trade != NULL)
                        $this->ubAlgo->updateBalance($trade);
                }
                

                if (isset($json['profit_table'])) {
                // api getLastResult
                    $tab = $this->api->getLastResult($json, $this->symbole);
                    foreach ($tab as $trade) {
                        if($this->symbole == $trade->getSymbole()){
                            $this->ubAlgo->setResultTrade($trade);
                        }
                    }
                }
                if (isset($json['authorize'])) {
                    $this->api->receivedAuthorize($json);
                    $client->send('{"transaction": 1, "subscribe": 1 }');
                    
                     $tradeRepo = $this->getContainer()->get('trade_repo');
                        $symboleRepo = $this->getContainer()->get('symbole_repo');
                        $symbole = $symboleRepo->findOneByName($this->currency); // 9 EURUSD /  3 VOL-25
                        if($tradeRepo->isTrading($symbole) == NULL  && $this->ubAlgo->isNotLocked()){
                            $categSignal = $this->getContainer()->get('category_signal_repo')->findOneById(3);
                            $parameterRepo = $this->getContainer()->get('parameter_repo');
                            $parameter = $parameterRepo->findOneById(1);
                            $this->tradeSignalPersister->randomSignal($symbole, $categSignal,  $tradeRepo->getLastTrade(), $parameter);
                            $this->ubAlgo->checkNewSignal($client, $this->api);
                        }
                }
                if (isset($json['balance'])) {
                    $this->api->receivedBalance($json);
                }
                if (isset($json['echo_req'])) {
                    $trade = $this->api->getResultTrade($json);
                    if($trade != NULL){
                        $this->ubAlgo->setResultTrade($trade);
                        $tradeRepo = $this->getContainer()->get('trade_repo');
                        $symboleRepo = $this->getContainer()->get('symbole_repo');
                        $symbole = $symboleRepo->findOneByName($this->currency); // 9 EURUSD /  3 VOL-25
                        if($tradeRepo->isTrading($symbole) == NULL  && $this->ubAlgo->isNotLocked()){
                            $categSignal = $this->getContainer()->get('category_signal_repo')->findOneById(3);
                            $parameterRepo = $this->getContainer()->get('parameter_repo');
                            $parameter = $parameterRepo->findOneById(1);
                            $this->tradeSignalPersister->randomSignal($symbole, $categSignal,  $tradeRepo->getLastTrade(), $parameter);
                            $this->ubAlgo->checkNewSignal($client, $this->api);
                        }
                    }
                }
                if (isset($json['proposal'])) {
                    $res = $this->api->receiveUpdateRate($json);
                    $symboleRepo = $this->getContainer()->get('symbole_repo');
                    $symbolePersister = $this->getContainer()->get('ub_core.symbole_persister');
                    $symbole = $symboleRepo->findOneBy(array('name' => $res['symbol']));
                    if ($res['contractType'] == 'CALL') {
                    $symbole->setLastCallRate($res['rate']);
                    } else {
                        $symbole->setLastPutRate($res['rate']);
                    }
                    $symbolePersister->persist($symbole);
                }
   // $client->close();
});
$client->open();


    




// ping pong
            $loop->addPeriodicTimer(20, function() use($client, $logger){
                $this->api->sendPing($client);
            });
            
            

            $loop->addPeriodicTimer(30, function() use($client, $logger){
                // api askLastResult
                $symboleRepo = $this->getContainer()->get('symbole_repo');
                 $tradeRepo = $this->getContainer()->get('trade_repo');
                 $symbole = $symboleRepo->findOneByName($this->currency);
                
                 $list = $tradeRepo->checkFakeTrade($symbole);
                 if (!empty($list)) {
                    $em = $this->getContainer()->get('doctrine.orm.entity_manager');
                    foreach ($list as $trade) {
                        $em->remove($trade);
                        $em->flush();
                    }
                }
            });

            

            
          /*
            $loop->addPeriodicTimer(5, function(Timer $timer) use ( $client) {
                // create radom signal  in green mode
               // if(!$this->ubAlgo->isModeOrange($this->ubAlgo->getSequenceOpen())) {
                    $symboleRepo = $this->getContainer()->get('symbole_repo');
                    $categSignal = $this->getContainer()->get('category_signal_repo')->findOneById(3);
                    $tradeRepo = $this->getContainer()->get('trade_repo');
                    $parameterRepo = $this->getContainer()->get('parameter_repo');
                    $parameter = $parameterRepo->findOneById(1);
                    
                   
                    $symbole = $symboleRepo->findOneByName($this->currency); // 9 EURUSD /  3 VOL-25
                    if($tradeRepo->isTrading($symbole) == NULL  && $this->ubAlgo->isNotLocked()){
                        $this->tradeSignalPersister->randomSignal($symbole, $categSignal,  $tradeRepo->getLastTrade(), $parameter);
                        $this->ubAlgo->checkNewSignal($client, $this->api);
                    }
               // }
            });*/
             
            /*
            $client->on('close', function($code = null, $reason = null) use ($loop) {
                print "Connection closed ({$code} - {$reason})\n";
                $loop->stop();


            });

            $client->on('error', function($code = null, $reason = null) use ($loop){
                print "Connection closed ({$code} - {$reason})\n";
                $loop->stop();
            }); 
            */
            

    }

    protected function configure()
    {
        $this
                ->setName('ub:portal')
                ->addArgument('currency', InputArgument::REQUIRED, 'The currency is required.')
                ->setDescription('Genere le robot qui va tourner en tache de fond')
                ->setHelp('Cette commande vous permet de facilement executer le robot avec symfony 3')
            ;
    }
  
    protected function interact(InputInterface $input, OutputInterface $output)
    {
    }
    
    protected function execute(InputInterface $input, OutputInterface $output)
    {
         $output->writeln([
        'Lancement de la commande Ultima Binary V1',
        '============',
        '',
    ]);
        
         $this->api = $this->getContainer()->get('ub_core.binary_api');
         $this->ubAlgo = $this->getContainer()->get('ub_core.algo');
         $this->tradeSignalPersister = $this->getContainer()->get('ub_core.trade_signal_persister');
         $this->currency = $input->getArgument('currency');
         $parameterRepo = $this->getContainer()->get('parameter_repo');
         $parameterPersister = $this->getContainer()->get('ub_core.parameter_persister');
                
            $parameter = $parameterRepo->findOneById(1);
            $parameterPersister->persist($parameter);
         //init signal receive when the progam was off
            $symboleRepo = $this->getContainer()->get('symbole_repo');
             $this->symbole = $symboleRepo->findOneByName($this->currency);
        $this->ubAlgo->setSymbole($this->symbole );
                //mode portal
                
                while (1) {
                    try {
                        $this->mainCore(PortalBinaryCommand::APIKEY, $this->loop, $this->logger);	
                        $this->loop->run();
                    } catch (\Exception $e){
                        echo 'Exception recue : ',  $e->getMessage(), " ligne ", $e->getLine(), "\n";
                        exit();
                    }
                }
      
    }
}
