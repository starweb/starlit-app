<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

/**
 * View helper that returns current url or creates a new one from provided parameters.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class Url extends AbstractViewHelper
{
    /**
     * Magic method called when object is called as a function.
     *
     * @param string $relativeUrl
     * @param array  $parameters Query parameters
     * @param string $argSeparator
     * @return string
     */
    public function __invoke($relativeUrl = null, array $parameters = [], $argSeparator = '&amp;')
    {
        if (!$this->view || !($request = $this->view->getRequest())) {
            throw new \LogicException('View request is required for this view helper');
        }

        $url = new \Starlit\Utils\Url($relativeUrl ?: $request->getRequestUri());
        $url->addQueryParameters($parameters, true, $argSeparator);

        return (string) $url;
    }
}
