<?php
/**
 * User: Amir Aslan Aslani
 * Date: 6/28/19
 * Time: 7:00 PM
 */

namespace Dor\Router;

class RouterResponseParameter{

    public $list = [];

    public function add(string $type, $value){
        $this->list[$type] = $value;
    }

    public function getValue(string $inputType){
        foreach($this->list as $type => $value){
            if($inputType == $type)
                return $value;
        }
        return null;
    }
}