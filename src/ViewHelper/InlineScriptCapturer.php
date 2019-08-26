<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

class InlineScriptCapturer extends Capturer
{
    public function end(): AbstractViewHelper
    {
        // Get the captured contents and end this output buffer
        $this->view->inlineJs .= \ob_get_clean();

        return $this;
    }
}
