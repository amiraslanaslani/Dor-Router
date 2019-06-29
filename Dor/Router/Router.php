<?php
/**
 * User: Amir Aslan Aslani
 * Date: 5/29/18
 * Time: 5:05 PM
 */

namespace Dor\Router;

use Dor\AnnotationParser\Annotation;
use Dor\AnnotationParser\MethodAnnotation;
use Dor\Http\Request;

class Router
{
    private $request;
    private $controllersPath;
    private $routesPath;
    private $controllersNamespace;

    private $isControllerFind = false;
    private $findRoute = array();
    private $rrp = null; // Router Response Parameter

    public function __construct(Request $request, string $controllersPath, string $routesPath, string $controllerNamespace = '', RouterResponseParameter $rrp = null)
    {
        $this->request = $request;
        $this->controllersPath = $controllersPath;
        $this->routesPath = $routesPath;
        $this->controllersNamespace = $controllerNamespace;
        $this->rrp = $rrp == null ? new RouterResponseParameter() : $rrp;
    }

    public function getResponse(){
        if($this->isControllerFind){

            $parameters = array();
            $methodReflection = $this->findRoute['method'];

            foreach ($methodReflection->getParameters() as $parameter){
                if($parameter->getClass() != null) {
                    // $param = $this->getResponseParametersValue($parameter->getType());
                    $param = $this->rrp->getValue($parameter->getType());
                    $parameters[] = $param;
                }
                else{
                    $parameters[] = null;
                }
            }

            // Get response of request and return it to response sender.
            $controllerObject = $this->findRoute['class']->newInstance();
            return $methodReflection->invokeArgs($controllerObject, $parameters);
        }
        return null;
    }

    public function iterateOverRoutes():bool{
        $this->iterateOverRouteFiles();
        $this->iterateOverControllers();

        return $this->isControllerFind;
    }

    private function iterateOverRouteFiles():bool{
        $fileRoutes = $this->loadRoutesFromDirectory($this->routesPath);

        foreach ($fileRoutes as $route) {
            if(! $route instanceof Route)
                continue;

            if($this->checkRouteObject($route)){
                $className = $route->getActionClass();
                $methodName = $route->getActionMethod();

                $filePath = $this->controllersPath . '/' . $className . '.php';
                include_once($filePath);
                $classReflector = new \ReflectionClass('\Dor\Controller\\' . $className);
                $methodReflector = $classReflector->getMethod($methodName);

                $this->setAsFound($classReflector, $methodReflector);
                return true;
            }
        }

        return $this->isControllerFind;
    }

    private function iterateOverControllers():bool{
        foreach (glob($this->controllersPath . "/*.php") as $filename) {
            $className = $this->controllersNamespace . basename($filename, '.php');
            $loadedClasses[] = $className;
            include_once($filename);
            $classReflector = new \ReflectionClass($className);
            $classAnnotation = new Annotation($className);

            foreach ($classReflector->getMethods() as $methodReflector) {

                if($this->isControllerFind)
                    break;

                $methodAnnotation = $classAnnotation->getMethod($methodReflector->name);
                $route = $methodAnnotation->hasAnnotation('Route') ? $this->createRouteFromAnnotation($methodAnnotation) : null;
                
                if($route != null && $this->checkRouteObject($route)){
                    $this->setAsFound($classReflector, $methodReflector);
                    return true;
                }
            }
        }

        return $this->isControllerFind;
    }

    private function setAsFound($classReflector, $methodReflector){
        $this->isControllerFind = true;
        $this->findRoute['class'] = $classReflector;
        $this->findRoute['method'] = $methodReflector;
    }

    private function createRouteFromAnnotation(MethodAnnotation $annotation):Route{
        $route = new Route();

        if(is_array($annotation->getAnnotation('Route')))
            foreach($annotation->getAnnotation('Route') as $rt)
                $route->uri($rt);
        else
            $route->uri($annotation->getAnnotation('Route'));

        if(is_array($annotation->getAnnotation('Method')))
            foreach($annotation->getAnnotation('Method') as $mt)
                $route->method($mt);
        else
            $route->method($annotation->getAnnotation('Method'));

        return $route;
    }

    private function checkRouteObject(Route $route):bool{
        return  count($route->getUri()) < 1 ? false :
                $this->checkRequestMethod($route) &&
                $this->checkRequestedURI($route);
    }

    private function checkRequestMethod(Route $route){
        foreach ($route->getMethod() as $method){
            if(strtolower(trim($method)) == $this->request->requestType){
                return true;
            }
        }
        return false;
    }

    private function checkRequestedURI(Route $route){
        foreach ($route->getUri() as $route){
            if($this->checkRoute($route))
                return true;
        }
        return false;
    }

    private function checkRoute($route){
        $preg1 = str_replace(
            "/",
            "\/",
            preg_replace(
                "/{(\w*)}/",
                "(\w|-)*",
                $route
            )
        );
        $isThisRoute = preg_match(
            '/^' . $preg1 . '$/',
            $this->request->uri,
            $r
        );

        // If this method is correct method to get response for this request
        if ($isThisRoute) {
            preg_match_all("/{(\w*)}/",$route,$params_key);

            $tmpUri = '~' . $this->request->uri . '~';
            $tmpRoute = '~' . $route . '~';
            $preg2 = preg_split ("/{(\w*)}/", $tmpRoute);
            $preg3 = '/' . str_replace("/","\/","(" . implode(")|(", $preg2) . ")") . '/';
            $preg4 = preg_split ($preg3 , $tmpUri);

            // Remove first and last element of array
            array_pop($preg4);
            array_shift($preg4);
            $this->request->inputParams = array_combine($params_key[1], $preg4);
            return true;
        }
        else{
            return false;
        }
    }

    private function loadRoutesFromDirectory($path){
        $routes = [];
        foreach(glob($path . '/*.php') as $filename){
            $routesOfFile = include($filename);
            $routes = array_merge($routes, $routesOfFile);
        }
        return $routes;
    }
}