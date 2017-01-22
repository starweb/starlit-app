<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpFoundation\Session\Session;
use Starlit\App\Provider\ServiceProviderInterface;
use Starlit\App\Provider\StandardServiceProvider;
use Starlit\App\Provider\ErrorServiceProvider;


/**
 * Main framework application and bootstrap class, which also serves as a micro service/dependency injection container.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class BaseApp
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
     * @var array
     */
    private $dicValues = [];

    /**
     * @var array
     */
    private $dicObjects = [];

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
     * Initializes the application.
     *
     * Put initialization code that should always be run here, but (NB!) make sure
     * no objects are actually instanced here, because then mock objects can't be
     * injected in their place. Place object instance code in the preHandle method.
     */
    protected function init()
    {
        $this->set('cli', (PHP_SAPI === 'cli'));

        if ($this->config->has('phpSettings')) {
            $this->setPhpSettings($this->config->get('phpSettings'));
        }

        $this->register(new ErrorServiceProvider());
        $this->register(new StandardServiceProvider());
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
     * Register service provider.
     *
     * @param ServiceProviderInterface $serviceProvider
     */
    public function register(ServiceProviderInterface $serviceProvider)
    {
        $serviceProvider->register($this);
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
    }

    /**
     * Handles an http request and returns a response.
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $this->set('request', $request);

        if (($preHandleResponse = $this->preHandle($request))) {
            return $preHandleResponse;
        }

        try {
            $controller = $this->getRouter()->route($request);

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
     * Set a DIC value.
     *
     * @param string $key
     * @param mixed  $value Wrap objects in a closure for lazy loading
     * @return BaseApp
     */
    public function set($key, $value)
    {
        $this->dicValues[$key] = $value;
        unset($this->dicObjects[$key]); // In case an object instance was stored for sharing

        return $this;
    }

    /**
     * Check if a DIC value/object exists.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->dicValues);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasInstance($key)
    {
        return isset($this->dicObjects[$key]);
    }

    /**
     * Get the shared instance of a DIC object, or a DIC value if it's not an object.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new \InvalidArgumentException(sprintf('No application value with key "%s" is defined.', $key));
        }

        // Get already instantiated object if it exist
        if (isset($this->dicObjects[$key])) {
            return $this->dicObjects[$key];
        }

        // Check if it's an invokable (closure/anonymous function)
        if (is_object($this->dicValues[$key]) && method_exists($this->dicValues[$key], '__invoke')) {
            $this->dicObjects[$key] = $this->dicValues[$key]($this);

            return $this->dicObjects[$key];
        }

        return $this->dicValues[$key];
    }

    /**
     * Get new instance of a DIC object.
     *
     * @param string $key
     * @return mixed
     */
    public function getNew($key)
    {
        if (!array_key_exists($key, $this->dicValues)) {
            throw new \InvalidArgumentException(sprintf('No application value with key "%s" is defined.', $key));
        } elseif (!is_object($this->dicValues[$key]) || !method_exists($this->dicValues[$key], '__invoke')) {
            throw new \InvalidArgumentException(sprintf('Application value "%s" is not invokable.', $key));
        }

        return $this->dicValues[$key]($this);
    }

    /**
     * Destroy a DIC object instance.
     *
     * Will force a new object to be created on next call.
     *
     * @param string $key
     */
    public function destroyInstance($key)
    {
        unset($this->dicObjects[$key]);
    }

    /**
     * Destroy all DIC object instances.
     *
     * Will force new objects to be created on next call.
     */
    public function destroyAllInstances()
    {
        $this->dicObjects = [];

        // To make sure objects (like database connections) are destructed properly. PHP might not destruct objects
        // until the end of execution otherwise.
        gc_collect_cycles();
    }

    /**
     * Magic method to get or set DIC values.
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        // getNew followed by an upper letter like getNewApple()
        if (preg_match('/^getNew([A-Z].*)/', $name, $matches)) {
            $key = lcfirst($matches[1]);

            return $this->getNew($key);
        } elseif (strpos($name, 'get') === 0) {
            $key = lcfirst(substr($name, 3));

            return $this->get($key);
        } elseif (strpos($name, 'set') === 0) {
            $argumentCount = count($arguments);
            if ($argumentCount !== 1) {
                throw new \BadMethodCallException("Invalid argument count[{$argumentCount}] for application {$name}()");
            }

            $key = lcfirst(substr($name, 3));

            return $this->set($key, $arguments[0]);
        } else {
            throw new \BadMethodCallException("No application method named {$name}()");
        }
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
    public function isCli()
    {
        return $this->get('cli');
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->get('session'); // Makes this method faster by bypassing __call() (which is quite slow).
    }

    /**
     * @return Router
     */
    public function getRouter()
    {
        return $this->get('router'); // Makes this method faster by bypassing __call() (which is quite slow).
    }

    /**
     * @return Request|null
     */
    public function getRequest()
    {
        return $this->has('request') ? $this->get('request') : null;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }
}
