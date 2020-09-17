<?php

namespace Pantheion\Routing;

use Pantheion\Facade\Arr;
use Pantheion\Facade\Str;
use Pantheion\Http\Request;

class Route
{
    const CONTROLLERS_FOLDER = "Pantheion\\Routing\\Controller";

    public function __construct($method, $url, $action)
    {
        $this->method = $method;
        $this->url = Str::startsWith($url, "/") ? $url : '/'.$url;
        $this->action = $action;
        $this->middleware = [];
        $this->name = null;
        $this->namespace = null;
    }

    protected function resolveUrl()
    {
        $url = explode('/', $this->url);
        
        return array_values(array_filter($url, function($value) {
            return $value !== "";
        }));
    }

    public function resolveAction()
    {
        return [$controller, $action] = explode(".", $this->action);
    }

    public function resolveParameters()
    {
        $url = $this->resolveUrl();
        
        $parameters = [];
        foreach($url as $i => $segment) {
            if(Str::contains($segment, ":")) {
                $parameters[$i] = $segment;
            }
        }

        return $parameters;
    }

    public function name(string $name)
    {
        $this->name = $name;
        return $this;
    }

    public function middleware($middleware)
    {
        if(is_array($middleware)) {
            $this->middleware = Arr::merge($this->middleware, $middleware);
            return $this;
        }

        $this->middleware[] = $middleware;
        return $this;
    }

    public function namespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    public function matches(Request $request)
    {
        if($this->method !== $request->method) {
            return false;
        }

        if(count($this->resolveUrl()) !== count($request->resolveUrl())) {
            return false;
        }

        $difference = array_diff($this->resolveUrl(), $request->resolveUrl());
        $notParameters = array_filter($difference, function($segment) {
            return !Str::contains($segment, ":") ? true : false;
        });

        return empty($notParameters);
    }

    public function resolveNamespace()
    {
        return is_null($this->namespace) ? 
            Route::CONTROLLERS_FOLDER . "\\" . $this->resolveAction()[0] :
            Route::CONTROLLERS_FOLDER . "\\" . $this->namespace . "\\" . $this->resolveAction()[0];
    }
}