<?php

namespace RFRoute\Route;

use RFRoute\Http\Request;
use RFRoute\Http\Response;

class Route
{
    private static array $routes = [];
    private static string $prefix = '';
    private static array $groupMiddlewares = [];
    private static array $globalMiddlewares = [];
    private static $errorHandler;
    private static $notFoundHandler;
    private static $maintenanceHandler;
    private static bool $maintenanceMode = false;

    private static function normalizePath(string $path): string
    {
        return '/' . implode('/', array_filter(explode('/', $path)));
    }

    private static function normalizeUri(string $uri): string
    {
        $parts = explode('/', $uri);
        $clean = [];
        foreach ($parts as $i => $p) {
            if ($p === '' && $i !== 0 && $i !== count($parts) - 1) {
                continue;
            }
            $clean[] = $p;
        }
        return '/' . implode('/', array_filter($clean, fn($p) => $p !== '' || $p === '0'));
    }

    public static function setErrorHandler(callable $handler)
    {
        self::$errorHandler = $handler;
    }

    public static function setNotFoundHandler(callable $handler)
    {
        self::$notFoundHandler = $handler;
    }

    public static function setMaintenanceHandler(callable $handler)
    {
        self::$maintenanceHandler = $handler;
    }

    public static function enableMaintenance(bool $status = true)
    {
        self::$maintenanceMode = $status;
    }

    public static function add(string|array $methods, string $path, callable|array $handler, array $middlewares = [])
    {
        $methods = is_array($methods) ? $methods : [$methods];
        $fullPath = rtrim(self::$prefix . '/' . ltrim($path, '/'), '/');
        if ($fullPath === '') $fullPath = '/';

        $regex = preg_replace_callback(
            '/\{(\??)([a-zA-Z_][a-zA-Z0-9_]*)(?::([^}]+))?\}/',
            function ($m) {
                $optional = $m[1] === '?';
                $name     = $m[2];
                $pattern  = $m[3] ?? '[^/]+';
                if ($optional) {
                    return "(?:/(?P<$name>$pattern))?";
                }
                return "(?P<$name>$pattern)";
            },
            $fullPath
        );

        $regex = '#^' . $regex . '$#';

        self::$routes[] = [
            'methods'     => $methods,
            'path'        => $fullPath,
            'regex'       => $regex,
            'handler'     => $handler,
            'middlewares' => array_merge(self::$globalMiddlewares, self::$groupMiddlewares, $middlewares),
        ];
    }

    public static function get(string $path, callable|array $handler, array $middlewares = [])    { self::add('GET', $path, $handler, $middlewares); }
    public static function post(string $path, callable|array $handler, array $middlewares = [])   { self::add('POST', $path, $handler, $middlewares); }
    public static function put(string $path, callable|array $handler, array $middlewares = [])    { self::add('PUT', $path, $handler, $middlewares); }
    public static function patch(string $path, callable|array $handler, array $middlewares = [])  { self::add('PATCH', $path, $handler, $middlewares); }
    public static function delete(string $path, callable|array $handler, array $middlewares = []) { self::add('DELETE', $path, $handler, $middlewares); }
    public static function options(string $path, callable|array $handler, array $middlewares = []){ self::add('OPTIONS', $path, $handler, $middlewares); }
    public static function any(string $path, callable|array $handler, array $middlewares = [])
    {
        $all = ['GET','POST','PUT','PATCH','DELETE','OPTIONS'];
        self::add($all, $path, $handler, $middlewares);
    }

    public static function group(string $prefix, callable $callback, array $middlewares = [])
    {
        $prevPrefix = self::$prefix;
        $prevGroup = self::$groupMiddlewares;
        self::$prefix = self::normalizePath($prevPrefix . '/' . $prefix);
        self::$groupMiddlewares = array_merge($prevGroup, $middlewares);
        $callback();
        self::$prefix = $prevPrefix;
        self::$groupMiddlewares = $prevGroup;
    }

    public static function middleware(array $middlewares, callable $callback)
    {
        $prevGlobal = self::$globalMiddlewares;
        self::$globalMiddlewares = array_merge($prevGlobal, $middlewares);
        $callback();
        self::$globalMiddlewares = $prevGlobal;
    }

    public static function dispatch(string $method, string $uri)
    {
        $method = strtoupper($method);
        $req = new Request();
        $res = new Response();

        if (self::$maintenanceMode) {
            if (self::$maintenanceHandler) {
                call_user_func(self::$maintenanceHandler, $req, $res);
            } else {
                $res->send("Service Unavailable - Maintenance Mode", 503);
            }
            return;
        }

        try {
            foreach (self::$routes as $route) {
                if (!in_array($method, $route['methods'])) continue;
                if (!preg_match($route['regex'], self::normalizeUri($uri), $matches)) continue;
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                foreach ($params as $k => $v) {
                    $req = $req->withAttribute($k, $v);
                }
                foreach ($route['middlewares'] as $middleware) {
                    $result = $middleware($req, $res);
                    if ($result === false) return;
                }
                $handler = $route['handler'];
                if (is_callable($handler)) {
                    $handler($req, $res);
                } elseif (is_array($handler) && count($handler) === 2) {
                    [$controller, $methodName] = $handler;
                    $instance = new $controller();
                    if (method_exists($instance, $methodName)) {
                        $instance->$methodName($req, $res);
                    } else {
                        throw new \RuntimeException("Method $methodName not found in controller $controller");
                    }
                } else {
                    throw new \RuntimeException("Invalid handler for route {$route['path']}");
                }
                exit;
            }
            if (self::$notFoundHandler) {
                call_user_func(self::$notFoundHandler, $req, $res);
            } else {
                $res->send("Route not found: $uri", 404);
            }
        } catch (\Throwable $e) {
            if (self::$errorHandler) {
                call_user_func(self::$errorHandler, $e, $req, $res);
            } else {
                $res->send("Internal Server Error: " . $e->getMessage(), 500);
            }
        }
        exit;
    }
}
