<?php
/**
 * User: Amir Aslan Aslani
 * Date: 5/6/18
 * Time: 6:14 AM
 */

namespace Dor\Router;

class Route
{
    const   POST = 'post',
            GET = 'get',
            DELETE = 'delete',
            PUT = 'put';

    private $uri = [];
    private $method = [];
    private $action = '';

    public static function add(){
        return new self();
    }

    // Setters
    public function action(string $act){
        $this->action = $act;
        return $this;
    }

    public function uri(string $uri){
        $this->uri[] = $uri;
        return $this;
    }

    public function method(string $method){
        $this->method[] = $method;
        return $this;
    }

    public function get(){
        return $this->method(self::GET);
    }

    public function post(){
        return $this->method(self::POST);
    }

    public function delete(){
        return $this->method(self::DELETE);
    }

    public function put(){
        return $this->method(self::PUT);
    }

    // Getters
    public function getAction(){
        return $this->action;
    }

    public function getActionPart(int $part){
        return explode('@', $this->getAction())[$part];
    }

    public function getActionClass(){
        return $this->getActionPart(0);
    }

    public function getActionMethod(){
        return $this->getActionPart(1);
    }

    public function getUri(){
        return $this->uri;
    }

    public function getMethod(){
        return $this->method;
    }
}