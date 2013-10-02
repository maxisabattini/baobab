<?php

use \baobab\Controller;

use \baobab\App;

use \baobab\Config;

class App_HeaderController extends Controller {

	public function __construct( $view, $params = array() ) {
		parent::__construct($view, $params);

		$app = App::getInstance();
		
		//Init vars here
        $defaultTitle = $app->config("title");

        $pageParams = $app->pageParams();

		$title = $pageParams["title"];
		
		if(!$title) {
		    $title = $defaultTitle;
		} else {
		    $title = str_replace("$*", $defaultTitle . " |", $title);
		}		
		
		$this->setVar("title" , $title );
		$this->setVar("keywords" , $pageParams["meta_keywords"] );
		$this->setVar("description" , $pageParams["meta_description"] );
		
		$this->setVar("charset", "UTF-8");


        $this->setVar("packed", $app->config("packed_resources") );
	}
}
