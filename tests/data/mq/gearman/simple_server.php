<?php

require dirname(dirname(dirname(dirname(__DIR__)))) . '/vendor/autoload.php';


$callback = function(GearmanJob $job){
    //echo $job->workload() . "\n";
    return 'I am server: ' . $job->workload();
};

$zero = new \rock\mq\GearmanQueue();
$zero->receive($callback);