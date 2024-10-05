<?php

namespace SMV;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use ReflectionException;
use ReflectionFunction;
use SMV\Collection\Collection;

class SMVApp
{


    private Collection $routes;
    private Collection $error_routes;
    private $url_method;
    private $endpoint;

    public static function builder(): SMVApp
    {
        return (new SMVApp())->init_routes();
    }

    private function init_routes(): static
    {


        $routes = (new Router())->build();
        $this->routes = Collection::from_array($routes->rules);
        $this->error_routes = Collection::from_array($routes->errors);
        try {
            $this->process_url_path();
        } catch (Exception $exception) {
            exit();
        }
        return $this;
    }

    private function process_endpoint(): void
    {
        $fullUri = urldecode($_SERVER['REQUEST_URI']);
        $fullUri = parse_url($fullUri)["path"];
        $fullUri = preg_replace('#/+#', '/', $fullUri);
        $basePath = dirname(urldecode($_SERVER['SCRIPT_NAME']));
        $uri = str_replace($basePath, '', $fullUri);
        $this->endpoint = empty($uri) ? '/' : $uri;
    }

    private function process_url_path(): void
    {
        (new Request())->build();
        $this->url_method = Request::$method;

        $this->process_endpoint();
        foreach ($this->routes->array() as $route) {

            try {
                $pattern = '#^' . preg_replace('/<(\w+)>/', '([^/]+)', $route->rule) . '$#';
                if (preg_match($pattern, $this->endpoint, $matches)) {
                    $this->checkAllowedMethods($route->method, Request::$method);
                    if (($ar = array_slice($matches, 1)) && $this->isDefined($ar)) {
                        continue;
                    }

                    $this->dispatch($route->rule, $route->controller, array_slice($matches, 1));

                    return;
                }

            } catch (Exception $exception) {

//                error_log("Error processing route: " . $exception->getMessage());
            }

        }
        $this->manage_bad_request();
    }

    private function dispatch(mixed $rule, callable $callback, $args): void
    {
        try {
            $rfc = new ReflectionFunction($callback);
            $p = $rfc->getParameters();
            $required = count($p);
            $found = count($args);
            if ($required < $found) {
                exit("Too many parameters passed");
            }
            if ($required > $found) {
                exit("Less parameters passed, required: $required parameters, found: $found");
            }

            preg_match_all('/<([^>]+)>/', $rule, $defined);
            foreach ($p as $index => $param) {
                $name_defined = $defined[1][$index];
                $expectedName = $param->getName();

                if ($name_defined !== $expectedName) {
                    exit("Undefined $expectedName.  (Expected parameter '$name_defined', found '$expectedName' at position $index.)");
                }
            }
            if (is_callable($callback)) {
                ob_start();
                $res = call_user_func_array($callback, $args);
                ob_end_clean();
                if (is_string($res))
                    print_r($res);
                elseif (is_array($res))
                    print_r(json_encode($res));
                else
                    echo "function did not return valid response";

            }
        } catch (Exception $e) {

        }
    }

    private function checkAllowedMethods($rootMethods, $m): void
    {
        if (!in_array($m, $rootMethods))
            self::handleError(403);
    }

    #[NoReturn] private static function handleError(int $code): void
    {
        exit("method not allowed");
    }

    private function isDefined($v, $s = false): bool
    {
        if (!$s)
            $v = implode("/", $v);
        foreach ($this->routes->array() as $r) {
            if ($r->rule === "/" . $v)
                return true;
        }
        return false;

    }

    private function isErrorPageDefined($v)
    {
        foreach ($this->error_routes->array() as $r) {
            if ($r->rule === $v) {
                return $r->controller;
            }
        }
        return null;

    }

    private function manage_bad_request(): void
    {
        $route = $this->isErrorPageDefined(404);
        if (is_callable($route)) {
            $this->dispatch(404, $route, []);
            return;
        }
        echo "Page not found";
    }
}
