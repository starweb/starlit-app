<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb / Ehandelslogik i Lund AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Starlit\Utils\Str;

/**
 * Class for routing URL request to controllers.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class Router
{
    /**
     * @var BaseApp
     */
    protected $app;

    /**
     * @var RouteCollection
     */
    protected $routes;

    /**
     * @var string
     */
    protected $defaultModule = '';

    /**
     * @var string
     */
    protected $defaultController = 'index';

    /**
     * @var string
     */
    protected $defaultAction = 'index';

    /**
     * @var string
     */
    protected $controllerClassSuffix = 'Controller';

    /**
     * @var string
     */
    protected $actionMethodSuffix = 'Action';

    /**
     * Constructor.
     *
     * @param BaseApp $app
     * @param array   $options
     */
    public function __construct(BaseApp $app, array $options = [])
    {
        $this->app = $app;
        $this->routes = new RouteCollection();

        $this->setOptions($options);

        // Default routes
        $this->addRoute(new Route('/'));
        $this->addRoute(new Route('/{action}', [], ['action' => '[a-z-]+']));
        $this->addRoute(new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']));
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        if (isset($options['defaultModule'])) {
            $this->defaultModule = $options['defaultModule'];
        }

        if (isset($options['defaultController'])) {
            $this->defaultController = $options['defaultController'];
        }

        if (isset($options['defaultAction'])) {
            $this->defaultAction = $options['defaultAction'];
        }

        if (!empty($options['routes'])) {
            foreach ($options['routes'] as $path => $routeConfig) {
                $defaults = isset($routeConfig['defaults']) ? $routeConfig['defaults'] : [];
                $requirements = isset($routeConfig['requirements']) ? $routeConfig['requirements'] : [];
                $this->addRoute(new Route($path, $defaults, $requirements));
            }
        }
    }

    /**
     * @return string
     */
    public function getDefaultModule()
    {
        return $this->defaultModule;
    }

    /**
     * @return string
     */
    public function getDefaultController()
    {
        return $this->defaultController;
    }

    /**
     * @return string
     */
    public function getDefaultAction()
    {
        return $this->defaultAction;
    }

    /**
     * Add route.
     *
     * @param Route $route
     */
    public function addRoute(Route $route)
    {
        // We use path as name (sees no use for names)
        $this->routes->add($route->getPath(), $route);
    }

    /**
     * Clear any added routes.
     */
    public function clearRoutes()
    {
        $this->routes = new RouteCollection();
    }

    /**
     * Get routes collection.
     *
     * @return RouteCollection|Route[]
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Resolve controller action and return callable properties.
     *
     * @param Request $request
     * @return AbstractController
     * @throws ResourceNotFoundException
     */
    public function route(Request $request)
    {
        // Match route
        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);

        $match = $matcher->match($request->getPathInfo());
        $request->attributes->add($match);


        // Set request properties with defaults
        $module = $request->attributes->get('module', $this->defaultModule);
        $controller = $request->attributes->get('controller', $this->defaultController);
        $action = $request->attributes->get('action', $this->defaultAction);
        $request->attributes->add(compact('module', 'controller', 'action'));

        // Get callable names
        $controllerClass = $this->getControllerClass($module, $controller);
        $actionMethod = $this->getActionMethod($action);

        // Check that controller exist
        if (!class_exists($controllerClass)) {
            throw new ResourceNotFoundException("Controller \"{$controllerClass}\" does not exist");
        }

        // Check that action exist (we don't use method_exists because PHP's method case insensitivity)
        $controllerMethods = get_class_methods($controllerClass);
        if (!in_array($actionMethod, $controllerMethods, true)) {
            throw new ResourceNotFoundException("Action method \"{$controllerClass}::{$actionMethod}\" does not exist");
        }

        $actualController = new $controllerClass($this->app, $request);

        return $actualController;
    }

    /**
     * @param string $module     Relative module namespace, like Core or Core\\Api
     * @param string $controller Controller name as lowercase separated string
     * @return string
     */
    public function getControllerClass($module, $controller)
    {
        $modulePrefix = '';
        if ($module) {
            $modulePrefix = '\\' . $module;
        }

        $controllerClassName = Str::separatorToCamel($controller, '-', true) . $this->controllerClassSuffix;

        return $modulePrefix . '\\Controller\\' . $controllerClassName;
    }

    /**
     * @param string $action Action name as lowercase separated string
     * @return string
     */
    public function getActionMethod($action)
    {
        return Str::separatorToCamel($action, '-') . $this->actionMethodSuffix;
    }
}
