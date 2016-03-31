<?php
namespace Starlit\App\ViewHelper;

/**
 */
class InlineScriptCapturerTest extends \PHPUnit_Framework_TestCase
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
