<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb / Ehandelslogik i Lund AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

/**
 * Inline JavaScript capture view helper.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class InlineScriptCapturer extends Capturer
{
    /**
     * End capturing (overridden).
     */
    public function end()
    {
        // Get the captured contents and end this output buffer
        $this->view->inlineJs .= ob_get_clean();
    }
}
