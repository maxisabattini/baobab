<?php

namespace baobab;

class Controller {
	protected $params;
    protected $view;
    protected $vars = array();

	public function __construct( $view, $params = array() ) {
		$this->params = $params;
		$this->view = $view;

        //Init vars here
    }

    public function exposeVarsAsGlobals() {
        //Expose vars
        foreach( $this->vars as $fieldName => $fieldValue ) {
            global $$fieldName;
            $$fieldName = $fieldValue;
        }
    }

    public function render() {
		//Load Description file
		$descFile = $this->getViewDescFile();
		if(file_exists($descFile)) {
			$vars = array();
			include $descFile;
			foreach( $vars as $k => $v ) {		
				if( is_int($k) ) {
					$$v = false;
				} else {
					$$k = $v;
				}
			}
		}

        //Log::info( "Render: ". $this->view );
        //Log::debug( $this->params );
        //Log::debug( $this->vars );

        //Expose vars
        foreach( $this->params as $fieldName => $fieldValue ) {
            $$fieldName = $fieldValue;
        }
        foreach( $this->vars as $fieldName => $fieldValue ) {
            $$fieldName = $fieldValue;
        }
		
        //Render
        include $this->view;
    }

    protected function setVar($name, $value) {
        $this->vars[$name]=$value;
    }

    protected function getVar($name) {
        return $this->vars[$name];
    }
	
	private function getViewDescFile() {	
		$baseFile = substr($this->view, 0, strlen( $this->view ) - 4 );		
		return $baseFile . ".desc.php";
	}
}