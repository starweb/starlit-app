<?php
namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

/**
 */
class PaginationTest extends TestCase
{
    public function testInvoke()
    {
        $mockRequest = $this->createMock('\Symfony\Component\HttpFoundation\Request');
        $mockRequest->query = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $mockRequest->query->expects($this->any())
            ->method('all')
            ->will($this->returnValue([]));

        $mockRequest->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/index/test'));


        $mockView = $this->createMock('\Starlit\App\View');
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
        $this->expectException('\LogicException');

        $paginationViewHelper = new Pagination();
        $paginationViewHelper(1, 10, 20);
    }
}
