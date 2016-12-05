<?php
namespace Starlit\App\ViewHelper;

/**
 */
class UrlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Url
     */
    protected $urlHelper;

    protected function setUp()
    {
        $this->urlHelper = new Url();

        $request = $this->createMock('\Symfony\Component\HttpFoundation\Request');
        $request->query = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $request->query->expects($this->any())
            ->method('all')
            ->will($this->returnValue([]));

        $request->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/hej/hopp'));

        $view = $this->createPartialMock('\Starlit\App\View', ['getRequest']);
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
        $this->expectException('\LogicException');

        $invokableObject = new Url();
        $invokableObject('test1');
    }
}
