<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\Provider;

use Starlit\App\BaseApp;
use Starlit\App\Router;
use Starlit\App\RouterInterface;
use Starlit\App\View;
use Starlit\App\ViewInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;

/**
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class StandardServiceProvider implements ServiceProviderInterface
{
    /**
     * @param BaseApp $app
     */
    public function register(BaseApp $app)
    {
        $app->set(BaseApp::class, function (BaseApp $app) {
            return $app;
        });

        $app->set(Request::class, function (BaseApp $app) {
            return Request::createFromGlobals();
        });

        $app->alias('sessionStorage', SessionStorageInterface::class);
        $app->set(SessionStorageInterface::class, function (BaseApp $app) {
            if ($app->isCli()) {
                return new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage();
            }
            return new \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage();
        });

        $app->alias('session', SessionInterface::class);
        $app->set(SessionInterface::class, Session::class);

        $app->alias('router', RouterInterface::class);
        $app->set(RouterInterface::class, function (BaseApp $app) {
            return new Router($app, $app->getConfig()->get('router', []));
        });

        $app->alias('view', ViewInterface::class);
        $app->set(ViewInterface::class, function (BaseApp $app) {
            return new View($app->getConfig()->get('view', []));
        });

        // Default response (force no cache)
        $app->alias('response', Response::class);
        $app->set(Response::class, function () {
            $response = new Response();

            $response->headers->addCacheControlDirective('no-cache', true);
            $response->headers->addCacheControlDirective('max-age', 0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);

            return $response;
        });
    }
}
