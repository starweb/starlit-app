<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ErrorHandling;

use Whoops\Handler\Handler;

class UserErrorPageHandler extends Handler
{
    /**
     * @var string
     */
    protected $errorPagePath;

    public function __construct(string $errorPagePath)
    {
        $this->errorPagePath = $errorPagePath;
    }

    public function handle(): ?int
    {
        if (php_sapi_name() === 'cli') {
            return Handler::DONE;
        }

        include $this->errorPagePath;

        return Handler::QUIT;
    }
}
