<?php declare(strict_types=1);
namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

/**
 */
class CapturerTest extends TestCase
{
    /**
     * @var Capturer
     */
    protected $capturerHelper;

    protected function setUp()
    {
        $this->capturerHelper = new Capturer();
    }

    public function testInvoke(): void
    {
        $invokableObject = $this->capturerHelper;
        $return = $invokableObject('test1');

        $this->assertInstanceOf(\Starlit\App\ViewHelper\Capturer::class, $return);
        $this->assertEquals('test1', $return->getContentKey());
    }

    public function testStart(): void
    {
       $preObLevel = \ob_get_level();

       $this->capturerHelper->start();

       $postObLevel = \ob_get_level();
       \ob_end_clean(); // We don't want an open output buffer

       $this->assertEquals($postObLevel, $preObLevel + 1);
    }

    public function testEnd(): void
    {
        $invokableObject = $this->capturerHelper;
        $helper = $invokableObject('test1');

        $helper->start();
        echo 'teeest';
        $return = $helper->end();

        $this->assertInstanceOf(\Starlit\App\ViewHelper\Capturer::class, $return);
        $this->assertEquals('teeest', $helper->getContent());
    }

    public function testEndFail(): void
    {
        $this->expectException(\LogicException::class);
        $this->capturerHelper->end();
    }

    public function testGetContentFail(): void
    {
        $this->expectException(\LogicException::class);
        $this->capturerHelper->getContent();
    }

    public function testGetContentFail2(): void
    {
        $invokableObject = $this->capturerHelper;
        $helper = $invokableObject('test1');

        $this->expectException(\LogicException::class);
        $helper->getContent();
    }

}
