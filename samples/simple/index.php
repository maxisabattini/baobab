<?php

//$cfg->get("statics_url")

$routes = array(
    
    "*"     => array(
        "title"     => "Site Test",
        "cache"     => false,
        "rewrite"   => false,
        "packed_resources" =>  true,
        "packed_resources_path" =>  "statics",
        "packed_resources_url" =>  "statics",
        "path" => dirname(__FILE__),
    ),
    
    //HOME
    
    "/"     =>      array(
        "title"                 => "$* Home",      
        "meta_description"      => "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
        "meta_keywords"         => "Lorem, ipsum, dolor, sit, amet, consectetur, adipisicing",

        "page"                  => "home",        
    ),

    "/contact"     =>      array(
        "title"                 => "$* Contact",      
        "meta_description"      => "Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
        "meta_keywords"         => "Lorem, ipsum, dolor, sit, amet, consectetur, adipisicing",

        "page"                  => "contact",    
    ),    
);

require_once "../../classes/app.class.php";

$app = \baobab\App::getInstance();
//$app->setPath( dirname(__FILE__) );
//\baobab\Log::$level = \baobab\Log::ALL;


\baobab\Log::info("//////////////////////////////////////////////////");
$app->route($routes);

