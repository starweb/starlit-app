<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

class MustacheTmplCapturer extends Capturer
{
    public function getScript(): string
    {
        return '<script type="text/x-mustache" id="' . $this->activeContentKey . '">' . "\n"
            . $this->getContent()
            . "</script>\n";
    }
}
