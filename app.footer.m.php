<!-- App Footer -->
<?php
$enq = \baobab\CssQueue::getInstance();
if($packed) {
    $enq->flushPacked();
} else {
    $enq->flush();
}
$enq = \baobab\JSQueue::getInstance();

if($packed) {
    $enq->flushPacked();
} else {
    $enq->flush();
}