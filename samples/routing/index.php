<?php 

require_once "../../classes/app.class.php";

$app = \baobab\App::getInstance();

$app->map("/", "home");
$app->map("/about", "about");

$app->route();


//var_dump($app);

/*
$url = $app->getUrlParts();

//$uri = $_SERVER['REQUEST_URI'];
$uri = "/" . $url["query"]; 

var_dump($uri);

//$route = new \baobab\Route("/how", $uri);
$route = new \baobab\Route("/how", $uri, "how");
var_dump($route);
*/