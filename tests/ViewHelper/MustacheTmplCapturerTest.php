<?php
namespace Starlit\App\ViewHelper;

/**
 */
class MustacheTmplCapturerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Capturer
     */
    protected $capturerHelper;

    protected function setUp()
    {
        $this->capturerHelper = new MustacheTmplCapturer();
    }

    public function testCapturer()
    {
        $invokableObject = $this->capturerHelper;
        $helper = $invokableObject('test1');
        
        $helper->start();
        echo 'teeest';
        $helper->end();
        
        $this->assertInstanceOf('\Starlit\App\ViewHelper\MustacheTmplCapturer', $helper);

        $this->assertInstanceOf('\Starlit\App\ViewHelper\Capturer', $helper);
        $this->assertContains('<script', $helper->getScript());
        $this->assertContains('teeest', $helper->getScript());
        $this->assertContains('</script>', $helper->getScript());
    }
    
}
