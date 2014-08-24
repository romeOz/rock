<?php

require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';


use PhpAmqpLib\Message\AMQPMessage;
use rock\mq\RabbitQueue;

$callback = function(AMQPMessage $msg) {
    //echo $msg->body . "\n";
    if ($msg->has('correlation_id') && $msg->has('reply_to')) {
        $msg->delivery_info['channel']->basic_publish(
            new AMQPMessage(
                'Hi! I am server: '. $msg->body,
                array('correlation_id' => $msg->get('correlation_id'))
            ),
            '',
            $msg->get('reply_to')
        );
    }
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$rabbit = new RabbitQueue();
$rabbit->id = 'simple';
$rabbit->receive($callback);