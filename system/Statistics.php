<?php

class Statistics{
    private $statistics;
    private $group_id;

    public function __construct($group_id){
        $this->group_id = $group_id;

        //$statistics_file = fopen('./statistics/'.$group_id.'.json', 'w') or die("Can't create file");
        //fclose($statistics_file);

        $this->statistics = json_decode(file_get_contents(__DIR__.'/../statistics/'.$group_id.'.json'), true);
    }

    public function writeCallType($type){
        if(!isset($this->statistics[$type])){
            $this->statistics[$type] = [];
            $this->statistics[$type][strtotime("today", time())] = 0;
        }
        $count = $this->statistics[$type][strtotime("today", time())];
        $this->statistics[$type][strtotime("today", time())] = $count+1;
    }

    public function close(){
        file_put_contents(__DIR__.'/../statistics/'.$this->group_id.'.json', json_encode($this->statistics));
    }
}