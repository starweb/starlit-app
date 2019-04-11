<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

use Starlit\Paginator\Paginator;

class Pagination extends AbstractViewHelper
{
    public function __invoke(int $currentPageNo, int $rowsPerPage, int $totalRowCount, array $options = []): string
    {
        if (!$this->view || !$this->view->getRequest()) {
            throw new \LogicException('View request is required for this view helper');
        }

        $paginator = new Paginator($currentPageNo, $rowsPerPage, $totalRowCount, $this->view->getRequest(), $options);

        return $paginator->getHtml();
    }
}
