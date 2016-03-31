<?php
namespace Starlit\App\ViewHelper;

/**
 */
class PaginationTest extends \PHPUnit_Framework_TestCase
{
    public function testInvoke()
    {
        $mockRequest = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $mockRequest->query = $this->getMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $mockRequest->query->expects($this->any())
            ->method('all')
            ->will($this->returnValue([]));

        $mockRequest->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/index/test'));


        $mockView = $this->getMock('\Starlit\App\View');
        $mockView->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($mockRequest));

        $paginationViewHelper = new Pagination();
        $paginationViewHelper->setView($mockView);
        $return = $paginationViewHelper(1, 10, 20);

        $this->assertContains('<div', $return);
    }

    public function testInvokeException()
    {
        $this->setExpectedException('\LogicException');

        $paginationViewHelper = new Pagination();
        $paginationViewHelper(1, 10, 20);
    }
}
