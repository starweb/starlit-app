<?php declare(strict_types=1);
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
        $app->alias('errorLogger', \Monolog\Logger::class);
        $app->set(\Monolog\Logger::class, function (BaseApp $app) {
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

        $app->alias('whoopsUserErrorPageHandler', \Starlit\App\ErrorHandling\UserErrorPageHandler::class);
        $app->set(\Starlit\App\ErrorHandling\UserErrorPageHandler::class, function (BaseApp $app) {
            return new \Starlit\App\ErrorHandling\UserErrorPageHandler(
                $app->getConfig()->getRequired('errorPagePath')
            );
        });

        $app->set('whoopsErrorHandler', function (BaseApp $app) {
            $plainTextHandler = new \Whoops\Handler\PlainTextHandler();
            $plainTextHandler->setLogger($app->get(\Monolog\Logger::class));
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
