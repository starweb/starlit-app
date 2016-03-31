<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb / Ehandelslogik i Lund AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\Provider;

use Starlit\App\BaseApp;

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
        $app->set('sessionStorage', function (BaseApp $app) {
            if ($app->isCli()) {
                return new \Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage();
            } else {
                return new \Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage();
            }
        });

        $app->set('session', function (BaseApp $app) {
            return new \Symfony\Component\HttpFoundation\Session\Session($app->getNew('sessionStorage'));
        });

        $app->set('router', function (BaseApp $app) {
            return new \Starlit\App\Router($app, $app->getConfig()->get('router', []));
        });

        $app->set('view', function (BaseApp $app) {
            return new \Starlit\App\View($app->getConfig()->get('view', []));
        });

        // Default response (force no cache)
        $app->set('response', function () {
            $response = new \Symfony\Component\HttpFoundation\Response();

            $response->headers->addCacheControlDirective('no-cache', true);
            $response->headers->addCacheControlDirective('max-age', 0);
            $response->headers->addCacheControlDirective('must-revalidate', true);
            $response->headers->addCacheControlDirective('no-store', true);

            return $response;
        });
    }
}
