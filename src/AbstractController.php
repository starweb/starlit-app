<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing;
use Starlit\Utils\Str;
use Starlit\Utils\Url;

/**
 * Base action controller.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
abstract class AbstractController
{
    /**
     * @var BaseApp
     */
    protected $app;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var View
     */
    protected $view;

    /**
     * @var bool
     */
    protected $autoRenderView = true;

    /**
     * @var string
     */
    protected $autoRenderViewScript;

    /**
     * Constructor.
     *
     * @param BaseApp $app
     * @param Request $request
     */
    final public function __construct(BaseApp $app, Request $request)
    {
        $this->app = $app;
        $this->request = $request;

        $this->view = $this->app->getNew(ViewInterface::class);
        $this->view->setRequest($this->request);

        $this->init();
    }

    /**
     * Initialization method meant to be overridden in descendant classes (optional).
     */
    protected function init()
    {
    }

    /**
     * Pre dispatch method meant to be overridden in descendant classes (optional).
     *
     * This method is called right before the actual action method is called/dispatched.
     * Override this instead of init() if access to dispatch properties is required (like
     * action name) or you need to return a response.
     *
     * @param string $action
     * @return Response|null
     */
    protected function preDispatch(string $action): ?Response
    {
        return null;
    }

    /**
     * @param bool $autoRenderView
     */
    public function setAutoRenderView(bool $autoRenderView): void
    {
        $this->autoRenderView = $autoRenderView;
    }

    /**
     * @param string $autoRenderViewScript
     */
    public function setAutoRenderViewScript(string $autoRenderViewScript): void
    {
        $this->autoRenderViewScript = $autoRenderViewScript;
    }

    /**
     * Dispatch the requested action
     *
     * @param string|null $action action id/name (lowercase, - word separation)
     * @param array       $actionArgs
     * @return Response
     * @throws Routing\Exception\ResourceNotFoundException
     */
    public function dispatch(string $action = null, array $actionArgs = []): Response
    {
        // If not special action is provided, try to get from request
        $router = $this->app->get(RouterInterface::class);
        $action = Str::camelToSeparator(
            $action ?: $router->getRequestAction($this->request),
            '-'
        );
        $actionMethod = $router->getActionMethod($action);
        $collectedArgs = $this->getCollectedDispatchArgs($actionMethod, $actionArgs);

        // Call pre dispatch method and return it's response if there is one (uncommon)
        $preDispatchResponse = $this->preDispatch($action);
        if ($preDispatchResponse instanceof Response) {
            return $preDispatchResponse;
        }

        // Call action method
        $actionResponse = \call_user_func_array([$this, $actionMethod], $collectedArgs);

        $this->postDispatch();

        return $this->getDispatchResponse($action, $actionResponse);
    }

    /**
     * @param string $actionMethod
     * @param array $actionArgs
     * @return array
     * @throws Routing\Exception\ResourceNotFoundException
     */
    protected function getCollectedDispatchArgs(string $actionMethod, array $actionArgs = []): array
    {
        // Check that method is a valid action method
        try {
            $reflectionMethod = new \ReflectionMethod($this, $actionMethod);
            if (!$reflectionMethod->isPublic() || $reflectionMethod->isConstructor()) {
                throw new Routing\Exception\ResourceNotFoundException(
                    "\"{$actionMethod}\" is not a valid action method."
                );
            }
        } catch (\ReflectionException $e) {
            throw new Routing\Exception\ResourceNotFoundException("\"{$actionMethod}\" action method does not exist.");
        }

        $params = $reflectionMethod->getParameters();
        $predefinedValues = \array_merge($this->request->attributes->all() ?? [], $actionArgs);

        try {
            $collectedArgs = $this->app->resolveParameters($params, $predefinedValues);
        } catch (\ReflectionException $e) {
            throw new \LogicException('Missing values for one or more action parameters');
        }

        return $collectedArgs;
    }

    /**
     * @param string $action
     * @param mixed $actionResponse
     * @return Response
     */
    protected function getDispatchResponse(string $action, $actionResponse): Response
    {
        if ($actionResponse instanceof Response) {
            return $actionResponse->prepare($this->request);
        } elseif ($actionResponse !== null) {
            return $this->app->get(Response::class)->setContent((string) $actionResponse)->prepare($this->request);
        } elseif ($this->autoRenderView) {
            $viewScript = $this->autoRenderViewScript ?: $this->getAutoRenderViewScriptName(
                $action,
                $this->app->get(RouterInterface::class)->getRequestController($this->request),
                $this->app->get(RouterInterface::class)->getRequestModule($this->request)
            );

            return $this->app->get(Response::class)->setContent($this->view->render($viewScript, true))
                ->prepare($this->request);
        } else {
            // Empty response if no other response is set
            return $this->app->get(Response::class)->setContent('')
                ->prepare($this->request);
        }
    }

    /**
     * @param string      $action
     * @param string      $controller
     * @param string|null $module
     * @return string
     */
    public function getAutoRenderViewScriptName(string $action, string $controller, $module = null): string
    {
        $viewScriptName = \implode('/', \array_filter([$module, $controller, $action]));

        return $viewScriptName;
    }

    /**
     * Post dispatch method meant to be overridden in descendant classes (optional).
     * This method is called right after an action method has returned it's response,
     * but before the dispatch method returns the response.
     */
    protected function postDispatch(): void
    {
    }

    /**
     * Forwards request to another action and/or controller
     *
     * @param string      $action     Action name as lowercase separated string
     * @param string|null $controller Controller name as lowercase separated string
     * @param string|null $module     Module name as lowercase separated string
     * @param array       $actionArgs
     * @return Response
     */
    protected function forward(
        string $action,
        string $controller = null,
        string $module = null,
        array $actionArgs = []
    ): Response {
        // Forward inside same controller (easy)
        if (empty($controller)) {
            return $this->dispatch($action, $actionArgs);
        // Forward to another controller
        } else {
            $router = $this->app->get(RouterInterface::class);
            $controller = $controller ?: $router->getRequestController($this->request);
            $module = $module ?: $router->getRequestModule($this->request);

            $controllerClass = $router->getControllerClass($controller, $module);
            $actualController = new $controllerClass($this->app, $this->request);

            // Set new request properties
            $this->request->attributes->add(\compact('module', 'controller', 'action'));

            return $actualController->dispatch($action, $actionArgs);
        }
    }

    /**
     * Get current or a new url merged with provided parameters.
     *
     * @param string $relativeUrl
     * @param array  $parameters
     * @return string
     */
    protected function getUrl(string $relativeUrl = null, array $parameters = []): string
    {
        // Make an absolute url of a new one url, or use the current one if none is provided
        if ($relativeUrl !== null) {
            $url = $this->request->getSchemeAndHttpHost() . $relativeUrl;
        } else {
            $url = $this->request->getSchemeAndHttpHost() . $this->request->getRequestUri();
        }

        if ($parameters) {
            $mergedParameters = \array_merge($this->get(), $parameters);
            $url = (string) (new Url($url))->addQueryParameters($mergedParameters);
        }

        return $url;
    }

    /**
     * Shortcut method to access GET/query parameters.
     *
     * @param string $key
     * @param mixed  $default
     * @return string|array
     */
    protected function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->query->all();
        }

        return $this->request->query->get($key, $default);
    }

    /**
     * Shortcut method to access POST/request parameters.
     *
     * @param string $key
     * @param mixed  $default
     * @return string|array
     */
    protected function post(string $key = null, string $default = null)
    {
        if ($key === null) {
            return $this->request->request->all();
        }

        return $this->request->request->get($key, $default);
    }
}
