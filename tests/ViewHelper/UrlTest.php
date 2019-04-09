<?php
namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

/**
 */
class UrlTest extends TestCase
{
    /**
     * @var Url
     */
    protected $urlHelper;

    protected function setUp()
    {
        $this->urlHelper = new Url();

        $request = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $request->query = $this->createMock(\Symfony\Component\HttpFoundation\ParameterBag::class);
        $request->query->expects($this->any())
            ->method('all')
            ->will($this->returnValue([]));

        $request->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/hej/hopp'));

        $view = $this->createPartialMock(\Starlit\App\View::class, ['getRequest']);
        $view->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->urlHelper->setView($view);
    }

    public function testInvokeRelativeUrl()
    {
        $invokableObject = $this->urlHelper;

        $this->assertEquals('/hej/plopp', $invokableObject('/hej/plopp'));
    }

    public function testInvoke()
    {
        $invokableObject = $this->urlHelper;

        $url = $invokableObject(null, ['a' => 1, 'b' => 2]);
        $this->assertEquals('/hej/hopp?a=1&amp;b=2', $url);
    }

    public function testInvokeException()
    {
        $this->expectException(\LogicException::class);

        $invokableObject = new Url();
        $invokableObject('test1');
    }
}
