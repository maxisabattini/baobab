<?php

use \baobab\Controller;

use \baobab\App;

use \baobab\Config;

class App_FooterController extends Controller {

	public function __construct( $view, $params = array() ) {
		parent::__construct($view, $params);

        $app = App::getInstance();

		//Init vars here        ;
        $this->setVar("packed", $app->config("packed_resources") );
	}
}