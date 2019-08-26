<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\Provider;

use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Starlit\App\BaseApp;
use Starlit\App\ErrorHandling\UserErrorPageHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;

class ErrorServiceProvider implements ServiceProviderInterface
{
    /**
     * @param BaseApp $app
     */
    public function register(BaseApp $app): void
    {
        $app->alias('errorLogger', Logger::class);
        $app->set(
            Logger::class, function (BaseApp $app) {
            $logger = new Logger('errorLogger');

            $handler = new ErrorLogHandler();
            if (!$app->isCli()) {
                $handler->pushProcessor(new WebProcessor());
                $format = '%level_name%: %message% %extra.server%%extra.url%';
            } else {
                $format = '%level_name%: %message%';
            }
            $handler->setFormatter(new \Monolog\Formatter\LineFormatter($format, null, true));
            $logger->pushHandler($handler);

            return $logger;
        });

        $app->set('whoopsDebugErrorPageHandler', function (BaseApp $app) {
            $prettyPageHandler = new PrettyPageHandler();
            if ($app->getConfig()->has('editor')) {
                $prettyPageHandler->setEditor($app->getConfig()->get('editor'));
            }

            return $prettyPageHandler;
        });

        $app->alias('whoopsUserErrorPageHandler', UserErrorPageHandler::class);
        $app->set(
            UserErrorPageHandler::class, function (BaseApp $app) {
            return new UserErrorPageHandler(
                $app->getConfig()->getRequired('errorPagePath')
            );
        });

        $app->set('whoopsErrorHandler', function (BaseApp $app) {
            $plainTextHandler = new PlainTextHandler();
            $plainTextHandler->setLogger($app->get(Logger::class));
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
