<?php

require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';


$callback = function(\ZMQSocket $server){
    $message = $server->recv();
    //echo $message . "\n";
    /* echo back the message */
    $server->send('I am server: ' . $message);
};
$zero = new \rock\mq\ZeroQueue();
$zero->dns = 'tcp://127.0.0.1:5557';
$zero->receive($callback);