<?php
use PhpAmqpLib\Message\AMQPMessage;
use rock\mq\RabbitQueue;

require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';

$callbackFoo = function(\ZMQSocket $server){
    /* echo back the message */
    $server->send('I am server: ' . 'foo');
};
$callbackBar = function(\ZMQSocket $server){
    /* echo back the message */
    $server->send('I am server: ' . 'bar');
};
$zero = new \rock\mq\ZeroQueue();
$zero->dns = 'tcp://127.0.0.1:5558';
$zero->blocking = false;
$zero->publish(['foo' => $callbackFoo, 'bar' => $callbackBar]);