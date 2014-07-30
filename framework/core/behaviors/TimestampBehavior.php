<?php

namespace rock\behaviors;


use rock\base\Behavior;
use rock\db\ActiveRecordInterface;
use rock\event\Event;

class TimestampBehavior extends Behavior
{
    public $attributes = [];

    public function before()
    {
        if (!$this->owner instanceof ActiveRecordInterface) {
            return;
        }
        $timestamp = time();

        foreach ($this->attributes as $eventName => $attributeNames) {
            Event::on(
                $this->owner,
                $eventName,
                function(Event $event) use ($attributeNames, $timestamp){
                    foreach ($attributeNames as $attribute) {
                        $event->owner->$attribute = $timestamp;
                    }
                }
            );
        }

    }

//    public function after()
//    {
//        if (!$this->owner instanceof ActiveRecordInterface) {
//            return;
//        }
//    }
}