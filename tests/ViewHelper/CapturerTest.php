<?php
namespace Starlit\App\ViewHelper;

/**
 */
class CapturerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Capturer
     */
    protected $capturerHelper;

    protected function setUp()
    {
        $this->capturerHelper = new Capturer();
    }

    public function testInvoke()
    {
        $invokableObject = $this->capturerHelper;
        $return = $invokableObject('test1');

        $this->assertInstanceOf(\Starlit\App\ViewHelper\Capturer::class, $return);
        $this->assertEquals('test1', $return->getContentKey());
    }

    public function testStart()
    {
       $preObLevel = ob_get_level();

       $this->capturerHelper->start();

       $postObLevel = ob_get_level();
       ob_end_clean(); // We don't want an open output buffer

       $this->assertEquals($postObLevel, $preObLevel + 1);
    }

    public function testEnd()
    {
        $invokableObject = $this->capturerHelper;
        $helper = $invokableObject('test1');

        $helper->start();
        echo 'teeest';
        $return = $helper->end();

        $this->assertInstanceOf(\Starlit\App\ViewHelper\Capturer::class, $return);
        $this->assertEquals('teeest', $helper->getContent());
    }

    public function testEndFail()
    {
        $this->expectException(\LogicException::class);
        $this->capturerHelper->end();
    }

    public function testGetContentFail()
    {
        $this->expectException(\LogicException::class);
        $this->capturerHelper->getContent();
    }

    public function testGetContentFail2()
    {
        $invokableObject = $this->capturerHelper;
        $helper = $invokableObject('test1');

        $this->expectException(\LogicException::class);
        $helper->getContent();
    }

}
