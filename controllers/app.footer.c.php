<?php

namespace baobab;

class AppFooterController extends Controller {

	public function __construct( $view, $params = array() ) {
		parent::__construct($view, $params);


		//Init vars here
        $this->setVar("packed", Config::get('packedResources',false));
	}
}