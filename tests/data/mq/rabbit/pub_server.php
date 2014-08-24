<?php
use PhpAmqpLib\Message\AMQPMessage;
use rock\mq\RabbitQueue;

require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';

$callbackFoo = function(AMQPMessage $req) {
    $msg = new AMQPMessage(
        'Hi! I am server: '. $req->delivery_info['routing_key'] . ' ' . $req->body,
        ['correlation_id' => $req->get('correlation_id')]
    );
    $req->delivery_info['channel']->basic_publish(
        $msg, '', $req->get('reply_to'));
    $req->delivery_info['channel']->basic_ack(
        $req->delivery_info['delivery_tag']);
};

$callbackBar = function(AMQPMessage $req) {

    $msg = new AMQPMessage(
        'Hi! I am server: '. $req->delivery_info['routing_key'],
        array('correlation_id' => $req->get('correlation_id'))
    );
    $req->delivery_info['channel']->basic_publish(
        $msg, '', $req->get('reply_to'));
    $req->delivery_info['channel']->basic_ack(
        $req->delivery_info['delivery_tag']);
};
$rabbit = new RabbitQueue();
$rabbit->exchange = 'direct_test';
$rabbit->type = 'direct';
$rabbit->publish(['foo' => $callbackFoo, 'bar' => $callbackBar]);