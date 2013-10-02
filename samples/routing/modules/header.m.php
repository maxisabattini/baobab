<?php
$app = \baobab\App::getInstance();
$enq = \baobab\CssQueue::getInstance();
$enq->addFile("//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css");

$app->render("app.header");

