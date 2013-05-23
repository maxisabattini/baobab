<?php

namespace baobab;

class Cli {

    protected $_out;
    protected $_in;

    protected $name = "Cli";

    protected $_silent = false;
    protected $_params = false;

    public function __construct(){
        if( BAO_CMD ) {
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $this->_out = new OutCmd();
            } else {
                    $this->_out = new OutCmdLinux();
            }
        } else {
            $this->_out = new OutWeb();
        }
    }

    protected function getParam($name) {        
        if( BAO_CMD ) {
            if( ! is_array( $this->_params ) ) {
                global $argv;
                $this->_params = array();
                foreach($argv as $arg) {
                    $parts = explode("=", $arg);
                    if( count($parts)> 1 ) {
                        $this->_params[ $parts[0] ] = $parts[1];
                    }
                }
            }
            if( isset($this->_params[$name]) ) {
                return $this->_params[$name];
            }
            return false;
        } else {
            return  $_REQUEST[$name];
        }
    }

    public function out( $string, $args=array() ){
        if(!$this->_silent) {
            $this->_out->out($string, $args);
        }
    }

    public function outHead($string, $args=array()){
        if(!$this->_silent) {
            $this->_out->outHead($string, $args);
        }
    }

    public function outLine($string, $args=array(), $mode="none"){
        if(!$this->_silent) {
            $this->_out->outLine($string, $args, $mode);
        }
    }

    public function outParagraph($string, $args=array()){
        if(!$this->_silent) {
            $this->_out->outParagraph($string, $args);
        }
    }

    public function execute(){
        if(!$this->_silent) {
            $this->_out->outHeader($this->name);
        }            

        //Call pre scripts
        $this->executeReal();
        //Call post scripts

        if(!$this->_silent) {
            $this->_out->outFooter();
        }            
    }

    protected function executeReal(){
            $this->out("Override me!");
    }
}


class OutCmdLinux {

    public function outHeader($string){
        print "\033[0m";
        print "\033[1m";
        $this->out("\n" . $string . "\n" );
        print "\033[0m";
        $this->out( str_pad("", strlen($string), "=" ) );
        print "\n";
    }

    public function outFooter(){
        $this->out("\n");
        print "\033[0m\n";
    }

	public function out($string, $args=array() ){
		if( $args ) {
			print vsprintf($string, $args );
		} else {
			print $string;
		}
	}

	public function outHead($string, $args=array()){
        print "\033[0m";
		print "\033[32m";
        print "\033[1m";
		$this->out($string, $args);
		print "\033[0m\n";
	}

    public function outLine($string, $args=array(), $mode="none"){
        print "\033[0m";
        switch( strtolower(trim($mode))) {
            case "error":
                print "\033[31m";
                break;
            case "warn":
                print "\033[35m";
                break;
            case "info":
                print "\033[36m";
                break;
            default:
                break;
        }
        $this->out($string, $args);
        print "\033[0m";
        print "\n";
    }

    public function outParagraph($string, $args=array()){
        print "\033[0m";
        print "\n\033[32m";
        $this->out($string, $args);
        print "\033[0m\n";
    }
}

class OutCmd {

    public function outHeader($string){
        $this->out("\n" . $string . "\n" );        
        $this->out( str_pad("", strlen($string), "=" ) );
        print "\n";
    }

    public function outFooter(){
        $this->out("\n");
        
    }

	public function out($string, $args=array() ){
		if( $args ) {
			print vsprintf($string, $args );
		} else {
			print $string;
		}
	}

    public function outLine($string, $args=array(), $mode="none"){

        switch( strtolower(trim($mode))) {
            case "error":                
				print "&!";
				$this->out($string, $args);
				print "&!";
                break;
            case "warn":
                print "!";
				$this->out($string, $args);
				print "!";
                break;
            case "info":
                print "'";
				$this->out($string, $args);
				print "'";
                break;
            default:
				$this->out($string, $args);
                break;
        }
        print "\n";
    }
}

class OutWeb {

    public function outHeader($string){
        ?>
        <html>
        <head>
            <title><?=$string?></title>
        </head>
        <body>
        <h1><?=$string?></h1>
        <br>
        <?php
    }

    public function outFooter(){
        ?>
        </body>
        </html>
        <?php
    }

    public function out($string, $args=array() ){
        if( $args ) {
            print vsprintf($string, $args );
        } else {
            print $string;
        }
    }

    public function outHead($string, $args=array()){
        print "<h2>";
        $this->out($string, $args);
        print "</h2>";
    }

    public function outLine($string, $args=array(), $mode="none"){
        switch( strtolower(trim($mode))) {
            case "error":
                print "<span style='color:red'>";
                break;
            case "warn":
                print "<span style='color:orange'>";
                break;
            case "info":
                print "<span style='color:#ffffe0'>";
                break;
            default:
                break;
        }
        $this->out($string, $args);
        print "</span>";
        print "<br>";
    }

    public function outParagraph($string, $args=array()){
        print "<p>";
        $this->out($string, $args);
        print "</p>";
    }
}
