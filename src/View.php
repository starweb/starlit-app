<?php
/**
 * Starlit App.
 *
 * @copyright Copyright (c) 2016 Starweb / Ehandelslogik i Lund AB
 * @license   BSD 3-Clause
 */

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Starlit\App\ViewHelper\AbstractViewHelper;

/**
 * Class for rendering view content.
 *
 * @author Andreas Nilsson <http://github.com/jandreasn>
 */
class View
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
        'capturer'             => '\Starlit\App\ViewHelper\Capturer',
        'mustacheTmplCapturer' => '\Starlit\App\ViewHelper\MustacheTmplCapturer',
        'pagination'           => '\Starlit\App\ViewHelper\Pagination',
        'url'                  => '\Starlit\App\ViewHelper\Url',
        'inlineScriptCapturer' => '\Starlit\App\ViewHelper\InlineScriptCapturer',
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
     * @var string
     */
    protected $layoutScript = '';

    /**
     * @var string
     */
    protected $layoutContent = '';

    /**
     * @var Request
     */
    private $request;

    /**
     * Constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    /**
     * Set options.
     *
     * @param array $options
     */
    public function setOptions(array $options)
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
    public function __set($name, $value)
    {
        $this->variables[$name] = $value;
    }

    /**
     * Magic method to get view variables.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->variables[$name])) {
            return null;
        }

        return $this->variables[$name];
    }

    /**
     * Magic method to test if view variable is set.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->variables[$name]);
    }

    /**
     * @param string $script
     */
    public function setLayout($script)
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
    public function render($script, $renderLayout = false)
    {
        // Check If script should be rendered with a layout
        if ($renderLayout && $this->layoutScript) {
            $this->layoutContent = $this->renderScript($script);

            return $this->renderScript($this->layoutScript);
        } else {
            return $this->renderScript($script);
        }
    }

    /**
     * Internal method that does the actual script rendering.
     *
     * @param string $script
     * @return string
     */
    protected function renderScript($script)
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

    /**
     * Returns set layout content.
     *
     * @return string
     */
    public function layoutContent()
    {
        return $this->layoutContent;
    }

    /**
     * Escape a string for output in view script.
     *
     * @param string $string
     * @param int    $flags
     * @return string
     */
    public function escape($string, $flags = ENT_QUOTES)
    {
        return htmlspecialchars($string, $flags, BaseApp::CHARSET);
    }

    /**
     * Returns view variable escaped for view script output.
     *
     * @param string $name
     * @return string
     */
    public function getEscaped($name)
    {
        if (!isset($this->variables[$name])) {
            return '';
        }

        return $this->escape($this->variables[$name]);
    }

    /**
     * @param string $helperName
     * @param string $className
     */
    public function addHelperClass($helperName, $className)
    {
        $this->helperClasses[$helperName] = $className;
    }

    /**
     * @param string $helperName
     * @return AbstractViewHelper
     */
    public function getHelper($helperName)
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
     *
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public function __call($name, array $arguments = [])
    {
        $helper = $this->getHelper($name);
        if (is_callable($helper)) {
            return call_user_func_array($helper, $arguments);
        }

        return $helper;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $value
     */
    public function setLayoutContent($value)
    {
        $this->layoutContent = $value;
    }
}
