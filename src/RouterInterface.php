<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

interface RouterInterface
{
    public function setOptions(array $options): void;

    public function getDefaultModule(): string;

    public function getDefaultController(): string;

    public function getDefaultAction(): string;

    public function addRoute(Route $route, string $name = null): void;

    public function clearRoutes(): void;

    public function getRoutes(): RouteCollection;

    /**
     * Resolve controller action and return callable properties.
     *
     * @throws ResourceNotFoundException
     */
    public function route(Request $request): AbstractController;

    public function getControllerClass(string $controller, string $module = null): string;

    public function getActionMethod(string $action): string;

    public function getRequestModule(Request $request): ?string;

    public function getRequestController(Request $request): string;

    public function getRequestAction(Request $request): string;
}
