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
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\Timer\Timer;

class PortalBinaryCommand extends ContainerAwareCommand
{
    private $loop;
    private $connector;
    private $app;
    private $api;
    private $ubAlgo;
    private $tradeSignalPersister;
    private $currency;
    private $symbole;
   // const APIKEY='hbMdhGGQErEeCXN';
   //const APIKEY='oGrJBdcWE3VPIOQ'; //demo elimina
   const APIKEY='mBH6b0KVabovDHQ'; // demo 2
    //const APIKEY = 'ZG9LgGWjNVxxmNi';
    
    
    function __construct(){
        parent::__construct();
         $this->loop = Factory::create();
    }
    
    
    
    public function mainCore($apiKey, $loop) {

// "app_id": "3008" 3020
        $connector = new Connector($this->loop);  
        $connector('wss://ws.binaryws.com/websockets/v3?app_id=3020')->then(
                function(WebSocket $conn) use ($loop, $apiKey) {


            $conn->send('{"authorize": "' . $apiKey . '"}');

            $conn->on('message', function(MessageInterface $msg) use ($conn) {
                print "Received: {$msg}\n";
                $json = json_decode($msg, true);
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
                        $this->ubAlgo->setResultTrade($trade);
                    }
                }
                if (isset($json['authorize'])) {
                    $this->api->receivedAuthorize($json);
                }
                if (isset($json['balance'])) {
                    $this->api->receivedBalance($json);
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
                
            });



// ping pong
           /* $loop->addPeriodicTimer(20, function(Timer $timer) use ($conn) {
                $this->api->sendPing($conn);
            });*/

            $loop->addPeriodicTimer(12, function(Timer $timer) use ( $conn) {
                // api askLastResult
         
                 $this->api->askLastResult($conn);
            });
            
            $loop->addPeriodicTimer(30, function(Timer $timer) use ( $conn) {
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


            

            
          
            $loop->addPeriodicTimer(5, function(Timer $timer) use ( $conn) {
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
                        $this->ubAlgo->checkNewSignal($conn, $this->api);
                    }
               // }
            });
             
            
            $conn->on('close', function($code = null, $reason = null) use ($loop) {
                print "Connection closed ({$code} - {$reason})\n";
                $loop->stop();


            });

            $conn->on('error', function($code = null, $reason = null) use ($loop){
                print "Connection closed ({$code} - {$reason})\n";
                $loop->stop();
            }); 
            
            
        });
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
                        $this->mainCore(PortalBinaryCommand::APIKEY, $this->loop);	
                        $this->loop->run();
                    } catch (\Exception $e){
                        echo 'Exception recue : ',  $e->getMessage(), " ligne ", $e->getLine(), "\n";
                        exit();
                    }
                }
      
    }
}
