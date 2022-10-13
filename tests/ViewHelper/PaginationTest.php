<?php declare(strict_types=1);

namespace Starlit\App\ViewHelper;

use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testInvoke(): void
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mockRequest->query = $this->createMock(\Symfony\Component\HttpFoundation\ParameterBag::class);
        $mockRequest->query->expects($this->any())
            ->method('all')
            ->will($this->returnValue([]));

        $mockRequest->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/index/test'));


        $mockView = $this->createMock(\Starlit\App\View::class);
        $mockView->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($mockRequest));

        $paginationViewHelper = new Pagination();
        $paginationViewHelper->setView($mockView);
        $return = $paginationViewHelper(1, 10, 20);

        $this->assertStringContainsString('<div', $return);
    }

    public function testInvokeException(): void
    {
        $this->expectException(\LogicException::class);

        $paginationViewHelper = new Pagination();
        $paginationViewHelper(1, 10, 20);
    }
}
