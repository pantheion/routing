<?php

namespace Pantheion\Routing;

use Pantheion\Http\Request;
use Pantheion\Facade\Str;
use Pantheion\Facade\Arr;

class Router
{
    protected $routes = [];

    public function __construct()
    {
        
    }

    public function get($url, $action)
    {
        return $this->addRoute("GET", $url, $action);
    }

    public function post()
    {

    }

    public function put()
    {

    }

    public function patch()
    {

    }

    public function delete()
    {

    }

    public function group(array $options, \Closure $routes)
    {
        $before = count($this->routes);
        $routes();
        $after = count($this->routes);
        $added = $after - $before;
        
        for($i = $after - $added; $i < $after; $i++) {
            $this->addGroupToRoute($this->routes[$i], $options);
        }
    }

    protected function addGroupToRoute(Route $route, array $options)
    {
        if(isset($options['prefix'])) {
            $route->url = Str::prepend($route->url, '/'.$options['prefix']);
        }

        if(isset($options['name'])) {
            $route->name = 
                !is_null($route->name) ? 
                $options['name'].$route->name : 
                $options['name'];
        }

        if(isset($options['namespace'])) {
            $route->namespace = 
                !is_null($route->namespace) ? 
                $options['namespace'].'\\'.$route->namespace :
                $options['namespace'];
        }

        if(isset($options['middleware'])) {
            if(!is_array($options['middleware'])) {
                throw new \Exception('Attribute "middleware" in a Route::group must be an array');
            }

            $reversed = array_reverse($options['middleware']);
            foreach($reversed as $middleware) {
                $route->middleware = Arr::prepend($route->middleware, $middleware);
            }
        }
    }

    public function resource()
    {

    }

    protected function addRoute(string $method, string $url, string $action)
    {
        return $this->routes[] = new Route($method, $url, $action);
    }

    public function handle(Request $request)
    {
        foreach($this->routes as $route) {
            if($route->matches($request)) {
                return $this->respond($request, $route);
            }
        }

        throw new \Exception('No Route matched the Request'); // REPLACE WITH 404
    }

    protected function respond(Request $request, Route $route) 
    {
        
    }

}