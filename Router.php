<?php

namespace Pantheion\Routing;

use App\Model\Post;
use Pantheion\Http\Request;
use Pantheion\Facade\Str;
use Pantheion\Facade\Arr;
use Pantheion\Facade\Middleware;
use Pantheion\Model\Model;

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
        $method = new \ReflectionMethod(
            $controller,
            $route->resolveAction()[1]
        );

        return $method->invokeArgs(new $controller, $this->resolveMethodParameters($method, $request, $route));
    }

    protected function resolveMethodParameters(\ReflectionMethod $method, Request $request, Route $route)
    {
        $requestParametersValues = $this->getRequestParametersValues($request, $route);

        $parameters = [];
        foreach($method->getParameters() as $parameter) {
            if($parameter->getClass() && Str::contains($parameter->getClass(), Request::class)) {
                $parameters[] = $request;
                continue;
            }

            if(in_array($parameter->getName(), array_keys($requestParametersValues))) {
                if($parameter->getClass() && $parameter->getClass()->getParentClass()->getName() === Model::class) {
                    $id = intval($requestParametersValues[$parameter->getName()]);
                    $class = $parameter->getClass()->getName();

                    $parameters[] = $class::findOrFail($id);
                    continue;
                }

                $parameters[] = $requestParametersValues[$parameter->getName()];
            }
        }

        return $parameters;
    }

    protected function getRequestParametersValues(Request $request, Route $route) 
    {
        $routeParameters = $route->resolveParameters();
        
        if (Arr::empty($routeParameters)) {
            return [];
        }
        
        $parameters = array_filter($request->resolveUrl(), function ($value, $index) use ($routeParameters) {
            return in_array($index, array_keys($routeParameters));
        }, ARRAY_FILTER_USE_BOTH);

        return array_combine(array_values($routeParameters), $parameters);
    }
}