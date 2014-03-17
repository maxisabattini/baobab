<?php

if( ! $this->getVar("noFlushJs") ) {
    $jsEnq = \baobab\JSQueue::getInstance();
    if($packed) {
        $jsEnq->flushPacked();
    } else {
        $jsEnq->flush();
    }
}

if( ! $this->getVar("noFlushCss") ) {
    $cssEnq = \baobab\CssQueue::getInstance();
    if($packed) {
        $cssEnq->flushPacked();
    } else {
        $cssEnq->flush();
    }
}