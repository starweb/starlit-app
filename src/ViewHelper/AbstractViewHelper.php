<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

use Starlit\App\View;

abstract class AbstractViewHelper
{
    /**
     * @var View
     */
    protected $view;

    public function setView(View $view): AbstractViewHelper
    {
        $this->view = $view;

        return $this;
    }
}
