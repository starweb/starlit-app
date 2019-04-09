<?php
namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

/**
 */
class MustacheTmplCapturerTest extends TestCase
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
        
        $this->assertInstanceOf(\Starlit\App\ViewHelper\MustacheTmplCapturer::class, $helper);

        $this->assertInstanceOf(\Starlit\App\ViewHelper\Capturer::class, $helper);
        $this->assertContains('<script', $helper->getScript());
        $this->assertContains('teeest', $helper->getScript());
        $this->assertContains('</script>', $helper->getScript());
    }
    
}
