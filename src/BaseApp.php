<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Starlit\App\Container\Container;
use Starlit\App\Provider\BootableServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Starlit\App\Provider\ServiceProviderInterface;
use Starlit\App\Provider\StandardServiceProvider;
use Starlit\App\Provider\ErrorServiceProvider;

/**
 * Main framework application and bootstrap class, which also serves as a micro service/dependency injection container.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class BaseApp extends Container
{
    /**
     * @const string
     */
    const CHARSET = 'UTF-8';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var ServiceProviderInterface[]
     */
    protected $providers = [];

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var bool
     */
    protected $isCli = false;

    /**
     * Constructor.
     *
     * @param array|Config $config
     * @param string       $environment Defaults to "production"
     */
    public function __construct($config = [], $environment = 'production')
    {
        if ($config instanceof Config) {
            $this->config = $config;
        } else {
            $this->config = new Config($config);
        }

        $this->environment = $environment;

        $this->init();
    }

    /**
     * Initializes the application object.

     * Override and put initialization code that should always be run as early as
     * possible here, but make sure no objects are actually instanced here, because then
     * mock objects can't be injected in their place. Place object instance code in
     * the preHandle method.
     */
    protected function init()
    {
        $this->isCli = (PHP_SAPI === 'cli');

        if ($this->config->has('phpSettings')) {
            $this->setPhpSettings($this->config->get('phpSettings'));
        }

        $this->registerProviders();
    }

    /**
     * Register service providers.
     */
    protected function registerProviders()
    {
        $this->register(new ErrorServiceProvider());
        $this->register(new StandardServiceProvider());
    }

    /**
     * Register service provider.
     *
     * @param ServiceProviderInterface $provider
     */
    public function register(ServiceProviderInterface $provider)
    {
        $this->providers[] = $provider;

        $provider->register($this);
    }

    /**
     * @param array  $phpSettings
     * @param string $prefix
     */
    protected function setPhpSettings($phpSettings, $prefix = '')
    {
        foreach ($phpSettings as $key => $val) {
            $key = $prefix . $key;
            if (is_scalar($val)) {
                ini_set($key, $val);
            } elseif (is_array($val)) {
                $this->setPhpSettings($val, $key . '.'); // Set sub setting with a recursive call
            }
        }
    }

    /**
     * Boot the application and its service providers.
     *
     * This is normally called by handle(). If requests are not handled
     * this method will have to called manually to boot.
     */
    public function boot()
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->providers as $provider) {
            if ($provider instanceof BootableServiceProviderInterface) {
                $provider->boot($this);
            }
        }

        $this->booted = true;
    }

    /**
     * Pre handle method meant to be overridden in descendant classes (optional).
     *
     * This method is called before an request is handled. Object instance code should be
     * place here and not in init() (more info about this at init()).
     *
     * @param Request $request
     * @return Response|null
     */
    protected function preHandle(Request $request)
    {
        return null;
    }

    /**
     * Post route method meant to be overridden in descendant classes (optional).
     * This method is called before an request is dispatched  but after it's routed. This means that  we know
     * it's a valid route and have access to the route attributes at this stage.
     *
     * @param Request $request
     * @return Response|null
     */
    protected function postRoute(Request $request)
    {
        return null;
    }

    /**
     * Handles an http request and returns a response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $this->alias('request', Request::class);
        $this->set(Request::class, $request);

        $this->boot();

        if (($preHandleResponse = $this->preHandle($request))) {
            return $preHandleResponse;
        }

        try {
            $controller = $this->resolveController($request);
            if (($postRouteResponse = $this->postRoute($request))) {
                return $postRouteResponse;
            }

            $response = $controller->dispatch();
        } catch (ResourceNotFoundException $e) {
            $response = $this->getNoRouteResponse($request);
        }

        $this->postHandle($request);

        return $response;
    }

    /**
     * Returns a response for no route / resource not found.
     *
     * @param Request $request
     * @return Response
     */
    protected function getNoRouteResponse(Request $request)
    {
        return new Response('Not Found', 404);
    }

    /**
     * Post handle method meant to be overridden in descendant classes (optional).
     * This method is called after an request has been handled but before
     * the response is returned from the handle method.
     *
     * @param Request $request
     */
    protected function postHandle(Request $request)
    {
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return bool
     */
    public function isCli(): bool
    {
        return $this->isCli;
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->has(Request::class) ? $this->get(Request::class) : null;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    private function resolveController(Request $request): ControllerInterface
    {
        $controller = $this->get(RouterInterface::class)->route($request);
        if (!$controller instanceof ControllerInterface) {
            throw new \LogicException('controller needs to implement ControllerInterface');
        }
        $controller->setApp($this);
        $controller->setRequest($request);
        if ($controller instanceof ViewAwareControllerInterface) {
            $controller->setView($this->getNew(ViewInterface::class));
        }
        $controller->init();

        return $controller;
    }
}
