<?php

namespace baobab;

class AppHeaderController extends Controller {

	public function __construct( $view, $params = array() ) {
		parent::__construct($view, $params);

		$app = App::getInstance();
		
		//Init vars here
		$title = $app->info("page", "title");
		$defaultTitle = $app->info("*", "title");
		
		if(!$title) {
		    $title = $defaultTitle;
		} else {
		    $title = str_replace("$*", $defaultTitle . " |", $title);
		}		
		
		$this->setVar("title" , $title );
		$this->setVar("keywords" , $app->info("page", "meta_keywords") );
		$this->setVar("description" , $app->info("page", "meta_description") );
		
		$this->setVar("charset", "UTF-8");
	}
}
