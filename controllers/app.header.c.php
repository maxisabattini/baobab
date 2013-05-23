<?php

namespace baobab;

class AppHeaderController extends Controller {

	public function __construct( $view, $params = array() ) {
		parent::__construct($view, $params);

		$app = App::getInstance();
		//Init vars here
		$info = $app->getPageInfo();
		
		$this->setVar("title" , isset($info["title"])? $info["title"]:'' );
		$this->setVar("keywords" , isset($info["meta_keywords"])? $info["meta_keywords"]:'' );
		$this->setVar("description" , isset($info["meta_description"])? $info["meta_description"]:'' );
		
		$this->setVar("charset", "UTF-8");
	}
}
