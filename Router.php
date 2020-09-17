<?php

namespace Pantheion\Routing;

use Pantheion\Http\Request;
use Pantheion\Facade\Str;
use Pantheion\Facade\Arr;
use Pantheion\Facade\Middleware;

class Router
{
    protected $routes = [];

    public function get($url, $action)
    {
        return $this->addRoute("GET", $url, $action);
    }

    public function post($url, $action)
    {
        return $this->addRoute("POST", $url, $action);
    }

    public function put($url, $action)
    {
        return $this->addRoute("PUT", $url, $action);
    }

    public function patch($url, $action)
    {
        return $this->addRoute("PATCH", $url, $action);
    }

    public function delete($url, $action)
    {
        return $this->addRoute("DELETE", $url, $action);
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
                return Middleware::handle($request, $route, function($request) use ($route) {
                    return app(Router::class)->respond($request, $route);
                });

                // if (!$response instanceof \Zephyr\Http\Response\RedirectResponse) Session::capture()->flushFlash();
            }
        }

        throw new \Exception('No Route matched the Request'); // REPLACE WITH 404
    }

    protected function respond(Request $request, Route $route) 
    {
        $controller = $route->resolveNamespace();
        $action = $route->resolveAction()[1];

        $params = $this->injectRequestInParameters(
            $this->parameterValues($request, $route),
            $request,
            $controller, 
            $action
        );

        return call_user_func_array([new $controller, $action], $params);
    }

    protected function parameterValues(Request $request, Route $route) 
    {
        $routeParams = $route->resolveParameters();

        if (Arr::empty($routeParams)) {
            return [];
        }

        return array_filter($request->resolveUrl(), function ($value, $index) use ($routeParams) {
            return in_array($index, array_keys($routeParams));
        }, ARRAY_FILTER_USE_BOTH);
    }

    protected function injectRequestInParameters(array $params, Request $request, string $controller, string $action)
    {
        $method = new \ReflectionMethod($controller, $action);
        $methodParams = $method->getParameters();

        $requestPosition = null;
        foreach($methodParams as $methodParam) {
            if($methodParam->getClass() && Str::contains($methodParam->getClass(), Request::class)) {
                $requestPosition = $methodParam->getPosition();
                break;
            }
        }

        if(is_null($requestPosition)) {
            return $params;
        }

        array_splice($params, $requestPosition, 0, [$request]);
        return $params;
    }
}