<?php

namespace Pantheion\Routing;

class RouteMapper
{
    protected $methods = [
        "get", "post", "put", "patch", "delete"
    ];

    public $router;

    public function __construct(Router $router)
    {
        $this->router = $router;   
    }

    public function resource()
    {

    }

    public function group(array $options, \Closure $routes)
    {
        return $this->router->group($options, $routes);
    }

    public function __call($method, $args)
    {
        if(!in_array($method, $this->methods)) {
            throw new \Exception("HTTP Method not available.");
        }

        return $this->router->{$method}(...$args);
    }
}