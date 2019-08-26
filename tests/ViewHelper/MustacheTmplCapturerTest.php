<?php declare(strict_types=1);

namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

class MustacheTmplCapturerTest extends TestCase
{
    /**
     * @var Capturer
     */
    protected $capturerHelper;

    protected function setUp(): void
    {
        $this->capturerHelper = new MustacheTmplCapturer();
    }

    public function testCapturer(): void
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
