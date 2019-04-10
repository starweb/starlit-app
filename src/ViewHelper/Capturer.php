<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App\ViewHelper;

/**
 * View helper to Capturing output content like inline javascript etc.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class Capturer extends AbstractViewHelper
{
    /**
     * @var array
     */
    protected $capturedContent = [];

    /**
     * @var string
     */
    protected $activeContentKey;

    /**
     * Magic method called when object is called as a function.
     *
     * @param string $contentKey
     * @return Capturer|AbstractViewHelper
     */
    public function __invoke(string $contentKey = null): AbstractViewHelper
    {
        if ($contentKey !== null) {
            $this->activeContentKey = $contentKey;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getContentKey(): string
    {
        return $this->activeContentKey;
    }

    /**
     * Start capturing.
     */
    public function start(): void
    {
        // Start capturing
        \ob_start();
    }

    /**
     * End capturing.
     *
     * @return Capturer|AbstractViewHelper
     */
    public function end(): AbstractViewHelper
    {
        if (empty($this->activeContentKey)) {
            throw new \LogicException('Specify view helper Capturer\'s content key for ending capture');
        }

        // Get the captured contents and end this output buffer
        $this->capturedContent[$this->activeContentKey] = \ob_get_clean();

        return $this;
    }

    /**
     * Get captured content.
     *
     * @return string
     */
    public function getContent(): string
    {
        if (empty($this->activeContentKey)) {
            throw new \LogicException('Specify view helper Capturer\'s content key for getting content');
        }
        if (!isset($this->capturedContent[$this->activeContentKey])) {
            $errorMsg = "View helper Capturer does not have any content for key \"{$this->activeContentKey}\"";
            throw new \LogicException($errorMsg);
        }

        return $this->capturedContent[$this->activeContentKey];
    }
}
