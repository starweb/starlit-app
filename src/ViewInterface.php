<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2019 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Starlit\App\ViewHelper\AbstractViewHelper;

interface ViewInterface
{
    public function __construct(array $options = []);

    public function setOptions(array $options): void;

    public function setLayout(string $script);

    /**
     * Render output (returns) of the specified view script (with layout if that exists).
     *
     * @param string $script Relative script path (without file extension!). Eg. "some-script" or "admin/some-script".
     * @param bool   $renderLayout
     * @return string
     */
    public function render(string $script, bool $renderLayout = false): string ;

    public function layoutContent(): string;

    public function escape(?string $string, int $flags = ENT_QUOTES): string;

    public function getEscaped(string $name): string;

    public function addHelperClass(string $helperName, string $className): void;

    public function getHelper(string $helperName): AbstractViewHelper;

    public function setRequest(Request $request): void;

    public function getRequest(): Request;

    public function setLayoutContent(string $value): void;
}
