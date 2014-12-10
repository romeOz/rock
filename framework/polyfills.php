<?php
if (!function_exists('boolval')) {
    function boolval($val) {
        return (bool)$val;
    }
}

if (!class_exists('\RecursiveTreeIterator')) {
    include __DIR__ .'/polyfills/RecursiveTreeIterator.php';
}

//if (!class_exists('\ZMQ')) {
//    include __DIR__ .'/polyfills/ZMQ.php';
//}