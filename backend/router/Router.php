<?php

class Router
{
    private array $routes = [];
    private array $groupStack = [];
    private array $middlewareStack = [];

    public function get(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    public function patch(string $path, $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function group(string $prefix, callable $callback, array $middleware = []): void
    {
        $this->groupStack[] = $prefix;
        $this->middlewareStack[] = $middleware;

        $callback($this);

        array_pop($this->groupStack);
        array_pop($this->middlewareStack);
    }

    public function dispatch(): void
    {
        $request = new Request();
        $response = new Response();

        if ($request->method === 'OPTIONS') {
            $this->sendCorsHeaders();
            http_response_code(200);
            exit;
        }

        $this->sendCorsHeaders();

        $matched = $this->matchRoute($request);

        if ($matched === null) {
            if ($this->pathExists($request->path)) {
                $response->error('Method not allowed', 405);
            } else {
                $response->error('Not found', 404);
            }
            return;
        }

        [$route, $params] = $matched;
        $request->params = $params;

        $this->runMiddlewarePipeline($route['middleware'], $request, $response, function ($req, $res) use ($route) {
            $this->callHandler($route['handler'], $req, $res);
        });
    }

    private function addRoute(string $method, string $path, $handler, array $middleware): self
    {
        $fullPath = $this->buildGroupPrefix() . $path;
        $fullPath = rtrim($fullPath, '/') ?: '/';

        $allMiddleware = array_merge($this->buildGroupMiddleware(), $middleware);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $allMiddleware,
            'regex' => $this->pathToRegex($fullPath),
            'paramNames' => $this->extractParamNames($fullPath),
        ];

        return $this;
    }

    private function buildGroupPrefix(): string
    {
        return implode('', $this->groupStack);
    }

    private function buildGroupMiddleware(): array
    {
        $middleware = [];
        foreach ($this->middlewareStack as $mw) {
            $middleware = array_merge($middleware, $mw);
        }
        return $middleware;
    }

    private function pathToRegex(string $path): string
    {
        $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $path);
        return '#^' . $regex . '$#';
    }

    private function extractParamNames(string $path): array
    {
        preg_match_all('/\{([a-zA-Z_]+)\}/', $path, $matches);
        return $matches[1];
    }

    private function matchRoute(Request $request): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }

            if (preg_match($route['regex'], $request->path, $matches)) {
                array_shift($matches);
                $params = [];
                foreach ($route['paramNames'] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }
                return [$route, $params];
            }
        }
        return null;
    }

    private function pathExists(string $path): bool
    {
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path)) {
                return true;
            }
        }
        return false;
    }

    private function runMiddlewarePipeline(array $middlewareList, Request $request, Response $response, callable $final): void
    {
        if (empty($middlewareList)) {
            $final($request, $response);
            return;
        }

        $mw = array_shift($middlewareList);
        $instance = is_object($mw) ? $mw : new $mw();

        $instance->handle($request, $response, function ($req, $res) use ($middlewareList, $final) {
            $this->runMiddlewarePipeline($middlewareList, $req, $res, $final);
        });
    }

    private function callHandler($handler, Request $request, Response $response): void
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            $controller->$method($request, $response);
        } elseif (is_callable($handler)) {
            $handler($request, $response);
        }
    }

    private function sendCorsHeaders(): void
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    }
}
