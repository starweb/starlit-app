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
interface BootableServiceProviderInterface extends ServiceProviderInterface
{
    public function boot(BaseApp $app): void;
}
