<?php

require_once 'src/Events.php';

define('CONFIG_PATH', dirname(__FILE__) . '/config.php');

class EventsTest extends PHPUnit_Framework_TestCase {

    //--------------------------------------------------------------------

//    public function testTriggerFiresInOrder()
//    {
//        $event = new \Myth\Events\Events(CONFIG_PATH);
//
//        $test_str = '';
//        $expected = 'firstmiddlelast';
//
//        $event->trigger('priorities', [&$test_str]);
//
//        $this->assertEquals($expected, $test_str);
//    }

    //--------------------------------------------------------------------

    public function testCanGetListeners()
    {
        $event = new \Myth\Events\Events(CONFIG_PATH);

        $listeners = $event->listeners('priorities');

        $this->assertTrue(count($listeners) === 3);
        $this->assertTrue($listeners[0] instanceof Closure);
        $this->assertTrue($listeners[1] instanceof Closure);
        $this->assertTrue($listeners[2] instanceof Closure);
    }

    //--------------------------------------------------------------------

    public function testRemoveAllListeners()
    {
        $event = new \Myth\Events\Events(CONFIG_PATH);

        $event->removeAllListeners('priorities', function(&$str) {
            $str .= 'first';
        });
        $listeners = $event->listeners('priorities');

        $this->assertTrue(count($listeners) === 0);
    }

    //--------------------------------------------------------------------
}