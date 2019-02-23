<?php
namespace Starlit\App;

use Starlit\App\Provider\BootableServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Route;

class BaseAppTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BaseApp
     */
    protected $app;

    protected $fakeConfig = ['testkey' => 'testval', 'phpSettings' => ['max_execution_time' => '5001', 'date' => ['timezone' => 'Africa/Kinshasa']]];

    protected $fakeEnv = 'blubb';

    protected function setUp()
    {
        $this->app = new BaseApp($this->fakeConfig, $this->fakeEnv);
    }

    /**
     * @covers \Starlit\App\Provider\ErrorServiceProvider::register
     * @covers \Starlit\App\Provider\StandardServiceProvider::register
     */
    public function testInit()
    {
        $this->assertInstanceOf(Session::class, $this->app->get(Session::class));
        $this->assertInstanceOf(Router::class, $this->app->get(RouterInterface::class));
        $this->assertInstanceOf(ViewInterface::class, $this->app->get(ViewInterface::class));

        $this->assertEquals($this->fakeConfig['testkey'], $this->app->getConfig()->get('testkey'));
        $this->assertEquals($this->fakeConfig['phpSettings']['max_execution_time'], ini_get('max_execution_time'));
        $this->assertEquals($this->fakeConfig['phpSettings']['date']['timezone'], ini_get('date.timezone'));

        // test setup default routes
        $router = $this->app->get(RouterInterface::class);
        $this->assertInstanceOf(Route::class, $router->getRoutes()->get('/'));
        $this->assertInstanceOf(Route::class, $router->getRoutes()->get('/{action}'));
        $this->assertInstanceOf(Route::class, $router->getRoutes()->get('/{controller}/{action}'));
    }

    public function testBoot()
    {
        $mockProvider = $this->createMock(BootableServiceProviderInterface::class);
        $mockProvider->expects($this->once())
            ->method('register')
            ->with($this->app);
        $mockProvider->expects($this->once())
            ->method('boot')
            ->with($this->app);

        $this->app->register($mockProvider);
        $this->app->boot();
    }

    public function testHandle()
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mockRouter = $this->createMock(Router::class);
        $mockController = $this->createMock(\Starlit\App\AbstractController::class);

        $mockController->expects($this->once())
            ->method('dispatch')
            ->will($this->returnValue(new Response('respnz')));

        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->returnValue($mockController));
        $this->app->set(RouterInterface::class, $mockRouter);


        $response = $this->app->handle($mockRequest);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals('respnz', $response->getContent());
    }

    public function testHandleNotFound()
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mockRouter = $this->createMock(Router::class);
        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\ResourceNotFoundException()));
        $this->app->set(RouterInterface::class, $mockRouter);

        $response = $this->app->handle($mockRequest);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testHandlePreHandleResponse()
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);

        $mockBaseApp = new TestBaseAppWithPreHandleResponse($this->fakeConfig, $this->fakeEnv);
        $response = $mockBaseApp->handle($mockRequest);

        $this->assertEquals('Pre handle response', $response->getContent());
    }

    public function testHandlePostRouteResponse()
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mockRouter = $this->createMock(Router::class);
        $mockController = $this->createMock(\Starlit\App\AbstractController::class);

        $mockBaseApp = new TestBaseAppWithPostRouteResponse($this->fakeConfig, $this->fakeEnv);

        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->returnValue($mockController));
        $mockBaseApp->set(RouterInterface::class, $mockRouter);

        $response = $mockBaseApp->handle($mockRequest);

        $this->assertEquals('Post route response', $response->getContent());
    }

    public function testGetValue()
    {
        $this->app->set('testKey', new \stdClass());

        $this->assertInstanceOf(\stdClass::class, $this->app->get('testKey'));
    }

    public function testGetValueCall()
    {
        $this->app->setSomeKey(new \stdClass());

        $this->assertInstanceOf(\stdClass::class, $this->app->getSomeKey());
    }

    public function testGetFail()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app->get('invalidKey');
    }

    public function testGetNew()
    {
        $this->assertInstanceOf(ViewInterface::class, $this->app->getNew(ViewInterface::class));
    }

    public function testGetNewUndefined()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app->getNew('bla');
    }

    public function testGetNewInvalid()
    {
        $this->app->set('someKey', 'someValue');

        $this->expectException(\InvalidArgumentException::class);
        $this->app->getNew('someKey');
    }

    public function testHasInstanceIsTrue()
    {
        $this->app->get(ViewInterface::class);
        $this->assertTrue($this->app->hasInstance(ViewInterface::class));
    }

    public function testHasInstanceIsFalse()
    {
        $this->assertFalse($this->app->hasInstance('nonExistentObject'));
    }

    public function test__callFail()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->app->setBla();
    }

    public function test__callFail2()
    {
        $this->expectException(\BadMethodCallException::class);
        $this->app->fail();
    }

    public function testDestroyInstance()
    {
        $count = 0;
        $this->app->set('testObj', function () use (&$count) {
            $obj = new \stdClass();
            $obj->id = ++$count;
            return $obj;
        });

        $obj1 = $this->app->get('testObj');
        $this->app->destroyInstance('testObj');
        $obj2 = $this->app->get('testObj');
        $obj3 = $this->app->get('testObj');

        $this->assertNotEquals($obj1->id, $obj2->id);
        $this->assertEquals($obj2->id, $obj3->id);
    }

    public function testDestroyAllInstances()
    {
        $count = 0;
        $this->app->set('testObj', function () use (&$count) {
            $obj = new \stdClass();
            $obj->id = ++$count;
            return $obj;
        });

        $obj1 = $this->app->get('testObj');
        $this->app->destroyAllInstances();
        $obj2 = $this->app->get('testObj');
        $obj3 = $this->app->get('testObj');

        $this->assertNotEquals($obj1->id, $obj2->id);
        $this->assertEquals($obj2->id, $obj3->id);
    }

    public function testIsCli()
    {
        $this->assertEquals(true, $this->app->isCli());
    }

    public function testGetEnvironment()
    {
        $this->assertEquals($this->fakeEnv, $this->app->getEnvironment());
    }

    public function testGetRequestReturnsRequest()
    {
        $mockRequest = $this->createMock(Request::class);
        $this->app->set(Request::class, $mockRequest);

        $this->assertSame($mockRequest, $this->app->get(Request::class));
    }

    public function testHasNoRequest()
    {
        $this->assertTrue($this->app->has(Request::class));
    }
}

class TestBaseAppWithPreHandleResponse extends BaseApp
{
    protected function preHandle(Request $request)
    {
        return new Response('Pre handle response');
    }
}
class TestBaseAppWithPostRouteResponse extends BaseApp
{
    protected function postRoute(Request $request)
    {
        return new Response('Post route response');
    }
}
