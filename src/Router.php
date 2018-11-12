<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
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
class Router implements RouterInterface
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
    protected $controllerNamespace = 'App\\Controller';

    /**
     * @var string|null
     */
    protected $defaultModule;

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
    public function setOptions(array $options): void
    {
        if (isset($options['controllerNamespace'])) {
            $this->controllerNamespace = $options['controllerNamespace'];
        }

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
            foreach ($options['routes'] as $nameOrPath => $routeConfig) {
                $this->addRouteFromConfig($nameOrPath, $routeConfig);
            }
        }
    }

    /**
     * @param string $name
     * @param array $routeConfig
     */
    private function addRouteFromConfig($name, array $routeConfig): void
    {
        if (array_key_exists('path', $routeConfig)) {
            $path = $routeConfig['path'];
        } else {
            $path = $name;
        }

        $defaults = isset($routeConfig['defaults']) ? $routeConfig['defaults'] : [];
        $requirements = isset($routeConfig['requirements']) ? $routeConfig['requirements'] : [];
        $methods = isset($routeConfig['methods']) ? $routeConfig['methods'] : [];
        $this->addRoute(new Route($path, $defaults, $requirements, [], '', [], $methods), $name);
    }

    /**
     * @return string
     */
    public function getDefaultModule(): string
    {
        return $this->defaultModule;
    }

    /**
     * @return string
     */
    public function getDefaultController(): string
    {
        return $this->defaultController;
    }

    /**
     * @return string
     */
    public function getDefaultAction(): string
    {
        return $this->defaultAction;
    }

    /**
     * Add route.
     *
     * @param Route $route
     * @param string|null $name
     */
    public function addRoute(Route $route, string $name = null): void
    {
        if ($name === null) {
            $name = $route->getPath();
        }
        $this->routes->add($name, $route);
    }

    /**
     * Clear any added routes.
     */
    public function clearRoutes(): void
    {
        $this->routes = new RouteCollection();
    }

    public function getRoutes(): RouteCollection
    {
        return $this->routes;
    }

    /**
     * Resolve controller action and return callable properties.
     *
     * @throws ResourceNotFoundException
     */
    public function route(Request $request): AbstractController
    {
        // Match route
        $context = new RequestContext();
        $context->fromRequest($request);
        $matcher = new UrlMatcher($this->routes, $context);

        $match = $matcher->match($request->getPathInfo());
        $request->attributes->add($match);


        // Set request properties with defaults
        $module = $this->getRequestModule($request);
        $controller = $this->getRequestController($request);
        $action = $this->getRequestAction($request);
        $request->attributes->add(compact('module', 'controller', 'action'));

        // Get callable names
        $controllerClass = $this->getControllerClass($controller, $module);
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
     * @param string      $controller Controller name as lowercase dash-separated string
     * @param string|null $module     Module as lowercase separated string
     * @return string
     */
    public function getControllerClass(string $controller, string $module = null): string
    {
        $moduleNamespace = null;
        if (!empty($module)) {
            $moduleNamespace = Str::separatorToCamel($module, '-', true);
        }

        $controllerClassName = Str::separatorToCamel($controller, '-', true) . $this->controllerClassSuffix;

        return '\\' . implode('\\', array_filter([
            $moduleNamespace,
            $this->controllerNamespace,
            $controllerClassName
        ]));
    }

    /**
     * @param string $action Action name as lowercase dash-separated string
     * @return string
     */
    public function getActionMethod(string $action): string
    {
        return Str::separatorToCamel($action, '-') . $this->actionMethodSuffix;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getRequestModule(Request $request): ?string
    {
        return $request->attributes->get('module', $this->defaultModule);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getRequestController(Request $request): string
    {
        return $request->attributes->get('controller', $this->defaultController);
    }

    /**
     * @param Request $request
     * @return string
     */
    public function getRequestAction(Request $request): string
    {
        return $request->attributes->get('action', $this->defaultAction);
    }
}
