<?php

//TODO: check if need laod base
//require_once "../../init.php";

baoimport("baobab/controller");
baoimport("baobab/enqueuer");
baoimport("baobab/js_enqueuer");

$app = \baobab\App::getInstance();

$app->route();
