<?php

namespace baobab;

class AppHeaderController extends Controller {

	public function __construct( $view, $params = array() ) {
		parent::__construct($view, $params);

		$app = App::getInstance();
		
		//Init vars here
		$title = $app->getInfo("page", "title");
		$defaultTitle = $app->getInfo("*", "title");
		
		if(!$title) {
		    $title = $defaultTitle;
		} else {
		    $title = str_replace("$*", $defaultTitle . " |", $title);
		}		
		
		$this->setVar("title" , $title );
		$this->setVar("keywords" , $app->getInfo("page", "meta_keywords") );
		$this->setVar("description" , $app->getInfo("page", "meta_description") );
		
		$this->setVar("charset", "UTF-8");
		
	}
}
