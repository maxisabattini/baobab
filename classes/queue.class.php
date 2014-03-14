<?php

namespace baobab;

require_once "cache.class.php";

class Queue {

    private $registered = array();

    private $to_do = array();

    private $queue = array();

    private $done = array();

    public function clear() {
        $this->registered=array();
        $this->to_do=array();
        $this->queue=array();
        $this->done=array();
    }

    public function add( $handle, $deps = array() ) {
        if ( isset($this->registered[$handle]) )
            return false;

        $dep = new \stdClass();
        $dep->handle=$handle;
        $dep->deps = $deps;
        $this->registered[$handle] = $dep;
        $this->queue[]=$handle;
        return true;
    }

    public function getAll() {

        $duration= 60 * 10 ;
        $key = "Queue-" . md5(print_r($this->registered, true));

        $cache = Cache::getInstance();
        $all = $cache->get($key, null , $duration );
        if(!$all) {
            $all=$this->do_items();
            if( ! is_null($all) ) {
                $cache->set($key, $all, $duration );
            }
        }

        Log::info("Queue for : $key" );
        Log::debug($all);

        return $all;
    }

    private function do_items( $handles = false ) {

        $handles = $this->queue;

        $this->all_deps( $handles );

        foreach( $this->to_do as $key => $handle ) {
            if ( !in_array($handle, $this->done, true) && isset($this->registered[$handle]) ) {

                if ( isset($this->registered[$handle]) )
                    $this->done[] = $handle;

                unset( $this->to_do[$key] );
            }
        }
        
        return $this->done;
    }

    private function all_deps( $handles, $recursion = false) {
        if ( !$handles = (array) $handles )
            return false;

        foreach ( $handles as $handle ) {
            $queued = in_array($handle, $this->to_do, true);

            if ( in_array($handle, $this->done, true) ) // Already done
                continue;           

            if ( $queued  ) // already queued and in the right group
                continue;

            $keep_going = true;
            if ( !isset($this->registered[$handle]) )
                $keep_going = false; // Item doesn't exist.
            elseif ( $this->registered[$handle]->deps && array_diff($this->registered[$handle]->deps, array_keys($this->registered)) )
                $keep_going = false; // Item requires dependencies that don't exist.
            elseif ( $this->registered[$handle]->deps && !$this->all_deps( $this->registered[$handle]->deps, true ) )
                $keep_going = false; // Item requires dependencies that don't exist.

            if ( ! $keep_going ) { // Either item or its dependencies don't exist.
                if ( $recursion )
                    return false; // Abort this branch.
                else
                    continue; // We're at the top level. Move on to the next one.
            }

            if ( $queued ) // Already grabbed it and its dependencies.
                continue;

            
            $this->to_do[] = $handle;
        }
        return true;
    }
}
