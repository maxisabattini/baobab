<?php

namespace baobab;

class Enqueuer {

    private $_vars = array();

    public function add( $name, $require = array() ) {
        $this->_vars[$name]=$require;
    }

    public function getAll() {

        $list = new DependencyList();
        foreach($this->_vars as $element => $require) {

            $item = new DependencyItem($element);
            $list->insertAfter( $item );    //insert on top

            if($require) {  //has dependencies
                foreach($require as $r) {
                    $founded = $list->find($r);
                    if($founded) {  //dependency already be pushed in list
                        $list->insertAfter( $item, $founded );  //Move item after dependency
                    } else {
                        $list->insertAfter( new DependencyItem($r) );   //insert dependency on top
                    }
                }
            }
        }
        return $list->getAsArray();
    }

}

class DependencyList {

    private $_head;

    public function __construct() {
        $this->_head = new DependencyItem(null);
    }

    public function insertAfter($item, $after=null) {

        if( ! $after ) {
            $after = $this->_head;
        }

        $next = $after->next;
        $after->next = $item;
        $item->next = $next;
    }

    public function find($data) {
        $iterator = $this->_head;
        while( $iterator->next ) {
            if( $iterator->data == $data ) {
                return $iterator;
            }

            $iterator=$iterator->next;
        }
        return false;
    }

    public function getAsArray() {
        $array=array();

        $iterator = $this->_head;
        if( ! $iterator->next ) {
              return $array;
        } else {
            $iterator=$iterator->next;
        }

        while( $iterator->next ) {
            $array[]=$iterator->data;
            $iterator=$iterator->next;   
        }

        return $array;
    }
}

class DependencyItem {

    public $data;
    public $next;

    public function __construct($data, $next = null) {
        $this->data = $data;
        $this->next = $next;
    }

}
