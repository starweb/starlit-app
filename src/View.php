<?php declare(strict_types=1);
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Starlit\App\ViewHelper\Capturer;
use Starlit\App\ViewHelper\InlineScriptCapturer;
use Starlit\App\ViewHelper\MustacheTmplCapturer;
use Starlit\App\ViewHelper\Pagination;
use Starlit\App\ViewHelper\Url;
use Symfony\Component\HttpFoundation\Request;
use Starlit\App\ViewHelper\AbstractViewHelper;

class View implements ViewInterface
{
    /**
     * @var string
     */
    protected $scriptRootPath = 'app/views';

    /**
     * @var string
     */
    protected $fileExtension = 'html.php';

    /**
     * Default helpers.
     *
     * @var array
     */
    protected $helperClasses = [
        'capturer'             => Capturer::class,
        'mustacheTmplCapturer' => MustacheTmplCapturer::class,
        'pagination'           => Pagination::class,
        'url'                  => Url::class,
        'inlineScriptCapturer' => InlineScriptCapturer::class,
    ];

    /**
     * @var array
     */
    protected $helpers = [];

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var string|null
     */
    protected $layoutScript;

    /**
     * @var string|null
     */
    protected $layoutContent;

    /**
     * @var Request
     */
    private $request;

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options): void
    {
        if (isset($options['scriptRootPath'])) {
            $this->scriptRootPath = $options['scriptRootPath'];
        }

        if (isset($options['fileExtension'])) {
            $this->fileExtension = $options['fileExtension'];
        }
    }

    /**
     * Magic method to set view variables.
     *
     * @param   string $name
     * @param   mixed  $value
     */
    public function __set(string $name, $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Magic method to get view variables.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (!isset($this->variables[$name])) {
            return null;
        }

        return $this->variables[$name];
    }

    /**
     * Magic method to test if view variable is set.
     */
    public function __isset(string $name): bool
    {
        return isset($this->variables[$name]);
    }

    public function setLayout(string $script): void
    {
        $this->layoutScript = $script;
    }

    /**
     * Render output (returns) of the specified view script (with layout if that exists).
     *
     * @param string $script Relative script path (without file extension!). Eg. "some-script" or "admin/some-script".
     * @param bool   $renderLayout
     * @return string
     */
    public function render(string $script, bool $renderLayout = false): string
    {
        // Check If script should be rendered with a layout
        if ($renderLayout && !empty($this->layoutScript)) {
            $this->layoutContent = $this->renderScript($script);

            return $this->renderScript($this->layoutScript);
        } else {
            return $this->renderScript($script);
        }
    }

    /**
     * Internal method that does the actual script rendering.
     */
    protected function renderScript(string $script): string
    {
        $fullScriptPath = $this->scriptRootPath . '/' . $script . '.' . $this->fileExtension;
        // We don't unit test invalid script because it slows down the test process an entire second or more
        if (!file_exists($fullScriptPath)) {
            throw new \RuntimeException("Couldn't find view script \"{$fullScriptPath}\"");
        }

        ob_start();

        include $fullScriptPath;

        return ob_get_clean();
    }

    public function layoutContent(): string
    {
        return $this->layoutContent;
    }

    /**
     * Escape a string for output in view script.
     */
    public function escape(?string $string, int $flags = \ENT_QUOTES): string
    {
        return htmlspecialchars((string) $string, $flags, BaseApp::CHARSET);
    }

    /**
     * Returns view variable escaped for view script output.
     */
    public function getEscaped(string $name): string
    {
        if (!isset($this->variables[$name])) {
            return '';
        }

        return $this->escape($this->variables[$name]);
    }

    public function addHelperClass(string $helperName, string $className): void
    {
        $this->helperClasses[$helperName] = $className;
    }

    public function getHelper(string $helperName): AbstractViewHelper
    {
        // If helper has not already been instantiated
        if (!isset($this->helpers[$helperName])) {
            // Check that helper is defined
            if (!isset($this->helperClasses[$helperName])) {
                throw new \InvalidArgumentException("No helper named \"{$helperName}\"");
            }

            $this->helpers[$helperName] = new $this->helperClasses[$helperName]();
            $this->helpers[$helperName]->setView($this);
        }

        return $this->helpers[$helperName];
    }

    /**
     * Magic method to call view helpers.
     * If the helper has not defined __invoke(), the helper object will be returned.
     * Otherwise, the result of the __invoke() is returned.
     */
    public function __call(string $name, array $arguments = [])
    {
        $helper = $this->getHelper($name);
        if (is_callable($helper)) {
            return \call_user_func_array($helper, $arguments);
        }

        return $helper;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setLayoutContent(string $value): void
    {
        $this->layoutContent = $value;
    }
}
