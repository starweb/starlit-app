<?php declare(strict_types=1);

namespace Starlit\App;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

class RouterTest extends TestCase
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockApp;

    /**
     * @var AbstractController
     */
    private $testController;

    protected function setUp(): void
    {
        $this->mockApp = $this->createMock(BaseApp::class);

        $this->view = $this->createMock(View::class);
        $this->mockApp->expects($this->any())
            ->method('getNew')
            ->with(ViewInterface::class)
            ->will($this->returnValue($this->view));

        $requestMock = $this->createMock(Request::class);
        $this->router = new Router($this->mockApp, [
            'controllerNamespace' => 'Controller'
        ]);

        $this->testController = (new class($this->mockApp, $requestMock) extends AbstractController {
            public function someOtherAction(): void
            {
            }
        });
    }

    public function testConstructAndOptions(): void
    {
        $fakeOptions = [
            'defaultModule' => 'modDef',
            'defaultController' => 'conDef',
            'defaultAction' => 'actDef',
            'routes' => [
                '/index/index' => ['defaults' => ['controller' => 'index', 'action' => 'index']],
                'foo-bar' => ['path' => '/foo/bar', 'defaults' => ['controller' => 'foo', 'action' => 'bar']],
            ],
        ];

        $tmpObject = new Router($this->mockApp, $fakeOptions);

        $this->assertEquals($fakeOptions['defaultModule'], $tmpObject->getDefaultModule());
        $this->assertEquals($fakeOptions['defaultController'], $tmpObject->getDefaultController());
        $this->assertEquals($fakeOptions['defaultAction'], $tmpObject->getDefaultAction());
    }

    public function testAddClearRoute(): void
    {
        $mockRoute = $this->createMock(\Symfony\Component\Routing\Route::class);
        $numberOfRoutes = $this->router->getRoutes()->count();
        $this->router->addRoute($mockRoute);

        $this->assertCount($numberOfRoutes + 1, $this->router->getRoutes());
        $this->router->clearRoutes();
        $this->assertCount(0, $this->router->getRoutes());
    }

    public function testAddRouteWithName(): void
    {
        $route = new Route('/foo/bar', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $numberOfRoutes = $this->router->getRoutes()->count();
        $this->router->addRoute($route, 'foo-bar');
        $routes = $this->router->getRoutes();

        $this->assertCount($numberOfRoutes + 1, $routes);

        $fetchedRoute = $routes->get('foo-bar');
        $this->assertEquals('/foo/bar', $fetchedRoute->getPath());
    }

    public function testAddRouteWithHttpMethods(): void
    {
        $route = new Route(
            '/foo/bar',
            [],
            ['controller' => '[a-z-]+', 'action' => '[a-z-]+'],
            [],
            '',
            [],
            ['POST', 'PUT', 'PATCH']
        );
        $numberOfRoutes = $this->router->getRoutes()->count();
        $this->router->addRoute($route);
        $routes = $this->router->getRoutes();
        $methods = $routes->get('/foo/bar')->getMethods();

        $this->assertCount($numberOfRoutes + 1, $routes);
        $this->assertCount(3, $methods);
    }

    public function testRoute(): void
    {
        $partiallyMockedRouter = $this->getMockBuilder(\Starlit\App\Router::class)
            ->setMethods(['getControllerClass'])
            ->setConstructorArgs([$this->mockApp])
            ->getMock();
        $partiallyMockedRouter->expects($this->once())
            ->method('getControllerClass')
            ->will($this->returnValue(\get_class($this->testController)));

        $route = new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $partiallyMockedRouter->addRoute($route);

        $request = Request::create('/index/some-other');
        $controller = $partiallyMockedRouter->route($request);

        $this->assertInstanceOf(\get_class($this->testController), $controller);
    }

    public function testRouteInvalidController()
    {
        $route = new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $this->router->addRoute($route);

        $request = Request::create('/index/some-other');

        $this->expectException(\Symfony\Component\Routing\Exception\ResourceNotFoundException::class);
        $this->router->route($request);
    }

    public function testRouteInvalidAction(): void
    {
        $partiallyMockedRouter = $this->getMockBuilder(\Starlit\App\Router::class)
            ->setMethods(['getControllerClass'])
            ->setConstructorArgs([$this->mockApp])
            ->getMock();
        $partiallyMockedRouter->expects($this->once())
            ->method('getControllerClass')
            ->will($this->returnValue(\get_class($this->testController)));

        $route = new Route('/{controller}/{action}', [], ['controller' => '[a-z-]+', 'action' => '[a-z-]+']);
        $partiallyMockedRouter->addRoute($route);

        $request = Request::create('/index/some-other-other');

        $this->expectException(\Symfony\Component\Routing\Exception\ResourceNotFoundException::class);
        $partiallyMockedRouter->route($request);
    }

    public function testGetControllerClass(): void
    {
        $controllerClass = $this->router->getControllerClass('test');
        $this->assertEquals('\Controller\TestController', $controllerClass);
    }

    public function testGetSeparatedControllerClass(): void
    {
        $controllerClass = $this->router->getControllerClass('test-Two');
        $this->assertEquals('\Controller\TestTwoController', $controllerClass);
    }

    public function testGetControllerClassWithModule(): void
    {
        $controllerClass = $this->router->getControllerClass('login', 'admin');
        $this->assertEquals('\Admin\Controller\LoginController', $controllerClass);
    }

    public function testGetActionMethod(): void
    {
        $actionMethod = $this->router->getActionMethod('random');
        $this->assertEquals('randomAction', $actionMethod);

        $actionMethod = $this->router->getActionMethod('other-random');
        $this->assertEquals('otherRandomAction', $actionMethod);
    }
}
