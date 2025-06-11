<?php
namespace core;

class Router {
    private array $routes = [];

    public function get(string $uri, callable|array $action) {
        $this->routes['GET'][$uri] = $action;
    }

    public function post(string $uri, callable|array $action) {
        $this->routes['POST'][$uri] = $action;
    }

    public function dispatch(string $requestUri, string $requestMethod) {
        $uri = parse_url($requestUri, PHP_URL_PATH);
        $method = strtoupper($requestMethod);

        if (isset($this->routes[$method][$uri])) {
            $handler = $this->routes[$method][$uri];

            if (is_array($handler)) {
                [$controllerClass, $methodName] = $handler;
                $controller = new $controllerClass();
                return $controller->$methodName();
            } else {
                return $handler();
            }
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Route not found"]);
        }
    }
}