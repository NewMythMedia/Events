<?php

// $events is the Events class instance, made available
// in the current scope when the file is read.

$events->on('priorities', function(&$str) {
    $str .= 'last';
}, EVENTS_PRIORITY_LOW);

$events->on('priorities', function(&$str) {
    $str .= 'middle';
}, EVENTS_PRIORITY_NORMAL);

$events->on('priorities', function(&$str) {
    $str .= 'first';
}, EVENTS_PRIORITY_HIGH);