<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

class Url extends AbstractViewHelper
{
    public function __invoke(string $relativeUrl = null, array $parameters = [], string $argSeparator = '&amp;'): string
    {
        if (!$this->view || !($request = $this->view->getRequest())) {
            throw new \LogicException('View request is required for this view helper');
        }

        $url = new \Starlit\Utils\Url($relativeUrl ?: $request->getRequestUri());
        $url->addQueryParameters($parameters, true, $argSeparator);

        return (string) $url;
    }
}
