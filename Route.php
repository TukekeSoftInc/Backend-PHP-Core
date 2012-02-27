<?php
namespace Backend\Core;
/**
 * File defining Route
 *
 * Copyright (c) 2011 JadeIT cc
 * @license http://www.opensource.org/licenses/mit-license.php
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in the
 * Software without restriction, including without limitation the rights to use, copy,
 * modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the
 * following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package CoreFiles
 */
/**
 * The Route class that uses the query string to help determine the controller, action
 * and arguments for the request.
 *
 * @package Core
 */
class Route
{
    /**
     * @var array An array of predefined routes
     */
    protected $_routes;

    public function addRoute($name, $route)
    {
        $this->_routes[$name] = $route;
    }

    public function getRoute($name)
    {
        return array_key_exists($name, $this->_routes) ? $this->_routes[$name] : null;
    }

    /**
     * The constructor for the class
     *
     * @param Request A request object to serve
     */
    public function __construct($routesFile = false)
    {
        $routesFile = $routesFile ?: PROJECT_FOLDER . 'configs/routes.yaml';
        if (!file_exists($routesFile)) {
            return false;
        }
        $routes = array();

        $ext  = pathinfo($routesFile, PATHINFO_EXTENSION);
        $info = pathinfo($routesFile);
        switch ($ext) {
        case 'json':
            $routes = json_decode(file_get_contents($routesFile), true);
            break;
        case 'yaml':
            if (function_exists('yaml_parse_file')) {
                $routes = \yaml_parse_file($routesFile);
            } else if (class_exists('\sfYamlParser')) {
                $yaml   = new \sfYamlParser();
                $routes = $yaml->parse(file_get_contents($routesFile));
            }
        }
        if (!array_key_exists('routes', $routes)) {
            $routes['routes'] = array();
        }
        if (!array_key_exists('controllers', $routes)) {
            $routes['controllers'] = array();
        }
        $this->_routes = $routes;
    }

    public function resolve($request)
    {
        //Setup and split the query
        if ($routePath = $this->checkDefinedRoutes($request)) {
            return $routePath;
        }
        return $this->checkGeneratedRoutes($request);
    }

    protected function checkGeneratedRoutes($request)
    {
        $query    = ltrim($request->getQuery(), '/');
        $queryArr = explode('/', $query);

        //Resolve the controller
        $controller = $queryArr[0];
        if (array_key_exists($controller, $this->_routes['controllers'])) {
            $controller  = $this->_routes['controllers'][$controller];
        } else {
            $controller = Utilities\Strings::className($queryArr[0]);
        }

        $action = strtolower($request->getMethod());
        switch ($action) {
        case 'get':
            if (count($queryArr) == 1) {
                $action = 'list';
            } else {
                $action = 'read';
            }
            break;
        case 'post':
            $action = 'create';
            break;
        case 'put':
            $action = 'update';
            break;
        case 'delete':
            break;
        }

        $options = array(
            'route'     => $request->getQuery(),
            'callback'  => $controller . '::' . $action,
            'arguments' => array_slice($queryArr, 1),
        );

        $routePath = new Utilities\RoutePath($options);
        if ($routePath->check($request)) {
            return $routePath;
        }
        return false;
    }

    protected function checkDefinedRoutes($request)
    {
        foreach ($this->_routes['routes'] as $name => $routeInfo) {
            $routePath = new Utilities\RoutePath($routeInfo);
            if ($routePath->check($request)) {
                return $routePath;
            }
        }
        return false;
    }
}