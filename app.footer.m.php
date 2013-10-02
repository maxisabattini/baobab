<!-- App Footer -->
<?php
$enq = \baobab\JSQueue::getInstance();

if($packed) {
    $enq->flushPacked();
} else {
    $enq->flush();
}