<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

use Starlit\Paginator\Paginator;

/**
 * Paginator view helper shortcut.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class Pagination extends AbstractViewHelper
{
    /**
     * Magic method called when object is called as a function.
     *
     * @param int   $currentPageNo
     * @param int   $rowsPerPage
     * @param int   $totalRowCount
     * @param array $options
     * @return string
     */
    public function __invoke($currentPageNo, $rowsPerPage, $totalRowCount, array $options = [])
    {
        if (!$this->view || !$this->view->getRequest()) {
            throw new \LogicException('View request is required for this view helper');
        }

        $paginator = new Paginator($currentPageNo, $rowsPerPage, $totalRowCount, $this->view->getRequest(), $options);

        return $paginator->getHtml();
    }
}
