<?php
namespace Starlit\App;

use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockApp;

    protected function setUp()
    {
        // Mock app
        $this->mockApp = $this->createMock('\Starlit\App\BaseApp');

        // Mock app view (needed for controller instantiation)
        $this->view = $this->createMock('\Starlit\App\View');
        $this->mockApp->expects($this->any())
            ->method('getNew')
            ->with('view')
            ->will($this->returnValue($this->view));

        $this->router = new Router($this->mockApp);
    }

    public function testConstructAndOptions()
    {
        $fakeOptions = [
            'defaultModule' => 'modDef',
            'defaultController' => 'conDef',
            'defaultAction' => 'actDef',
            'routes' => ['/index/index' => ['defaults' => ['controller' => 'index', 'action' => 'index']]],
        ];

        $tmpObject = new Router($this->mockApp, $fakeOptions);

        $this->assertEquals($fakeOptions['defaultModule'], $tmpObject->getDefaultModule());
        $this->assertEquals($fakeOptions['defaultController'], $tmpObject->getDefaultController());
        $this->assertEquals($fakeOptions['defaultAction'], $tmpObject->getDefaultAction());
    }

    public function testAddClearRoute()
    {
        $mockRoute = $this->createMock('\Symfony\Component\Routing\Route');

        $this->assertCount(3, $this->router->getRoutes());
        $this->router->addRoute($mockRoute);
        $this->assertCount(4, $this->router->getRoutes());
        $this->router->clearRoutes();
        $this->assertCount(0, $this->router->getRoutes());
    }

    public function testRoute()
    {
        // Mock controller get
        $partiallyMockedRouter = $this->getMockBuilder('\Starlit\App\Router')
            ->setMethods(['getControllerClass'])->setConstructorArgs([$this->mockApp])->getMock();
        $partiallyMockedRouter->expects($this->once())
            ->method('getControllerClass')
            ->will($this->returnValue('\Starlit\App\RouterTestController'));

        // Set routes
        $route = new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $partiallyMockedRouter->addRoute($route);

        // Route request
        $request = Request::create('/index/some-other');
        $controller = $partiallyMockedRouter->route($request);

        $this->assertInstanceOf('\Starlit\App\RouterTestController', $controller);
    }

    public function testRouteInvalidController()
    {
        // Set routes
        $route = new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $this->router->addRoute($route);

        // Route request
        $request = Request::create('/index/some-other');

        $this->expectException('\Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $this->router->route($request);
    }

    public function testRouteInvalidAction()
    {
        // Mock controller get
        $partiallyMockedRouter = $this->getMockBuilder('\Starlit\App\Router')
            ->setMethods(['getControllerClass'])->setConstructorArgs([$this->mockApp])->getMock();
        $partiallyMockedRouter->expects($this->once())
            ->method('getControllerClass')
            ->will($this->returnValue('\Starlit\App\RouterTestController'));

        // Set routes
        $route = new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $partiallyMockedRouter->addRoute($route);

        // Route request
        $request = Request::create('/index/some-other-other');

        $this->expectException('\Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $partiallyMockedRouter->route($request);
    }

    public function testGetControllerClass()
    {
        $controllerClass = $this->router->getControllerClass('Core', 'test');
        $this->assertEquals('\\Core\\Controller\\TestController', $controllerClass);

        $controllerClass = $this->router->getControllerClass('Core\\Api', 'some-name');
        $this->assertEquals('\\Core\\Api\\Controller\\SomeNameController', $controllerClass);
    }

    public function testGetActionMethod()
    {
        $actionMethod = $this->router->getActionMethod('random');
        $this->assertEquals('randomAction', $actionMethod);

        $actionMethod = $this->router->getActionMethod('other-random');
        $this->assertEquals('otherRandomAction', $actionMethod);
    }
}

class RouterTestController extends AbstractController
{
    public function someOtherAction()
    {
    }
}