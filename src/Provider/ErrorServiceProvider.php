<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\Provider;

use Starlit\App\BaseApp;

/**
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class ErrorServiceProvider implements ServiceProviderInterface
{
    /**
     * @param BaseApp $app
     */
    public function register(BaseApp $app)
    {
        $app->set('errorLogger', function (BaseApp $app) {
            $logger = new \Monolog\Logger('errorLogger');

            $handler = new \Monolog\Handler\ErrorLogHandler();
            if (!$app->isCli()) {
                $handler->pushProcessor(new \Monolog\Processor\WebProcessor());
                $format = '%level_name%: %message% %extra.server%%extra.url%';
            } else {
                $format = '%level_name%: %message%';
            }
            $handler->setFormatter(new \Monolog\Formatter\LineFormatter($format, null, true));
            $logger->pushHandler($handler);

            return $logger;
        });

        $app->set('whoopsDebugErrorPageHandler', function (BaseApp $app) {
            $prettyPageHandler = new \Whoops\Handler\PrettyPageHandler();
            if ($app->getConfig()->has('editor')) {
                $prettyPageHandler->setEditor($app->getConfig()->get('editor'));
            }

            return $prettyPageHandler;
        });

        $app->set('whoopsUserErrorPageHandler', function (BaseApp $app) {
            return new \Starlit\App\ErrorHandling\UserErrorPageHandler(
                $app->getConfig()->getRequired('errorPagePath')
            );
        });

        $app->set('whoopsErrorHandler', function (BaseApp $app) {
            $plainTextHandler = new \Whoops\Handler\PlainTextHandler();
            $plainTextHandler->setLogger($app->get('errorLogger'));
            if (!$app->isCli()) {
                $plainTextHandler->loggerOnly(true);
            }

            return $plainTextHandler;
        });

        $app->set('whoops', function (BaseApp $app) {
            $whoops = new \Whoops\Run();

            if (ini_get('display_errors')) {
                $whoops->pushHandler($app->get('whoopsDebugErrorPageHandler'));
            } elseif ($app->getConfig()->has('errorPagePath')) {
                $whoops->pushHandler($app->get('whoopsUserErrorPageHandler'));
            }

            // Handles cli output and logging
            $whoops->pushHandler($app->get('whoopsErrorHandler'));

            return $whoops;
        });
        $app->get('whoops')->register();
    }
}
