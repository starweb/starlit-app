<?php declare(strict_types=1);
namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

/**
 */
class InlineScriptCapturerTest extends TestCase
{
    public function testEnd()
    {
        $helper = new InlineScriptCapturer();
        $view = new \Starlit\App\View();
        $view->inlineJs = 'q';
        $helper->setView($view);

        $helper->start();
        echo 'teeest';
        $helper->end();

        $this->assertEquals('qteeest', $view->inlineJs);
    }

}
