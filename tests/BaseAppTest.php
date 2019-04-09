<?php declare(strict_types=1);
namespace Starlit\App;

use PHPUnit\Framework\TestCase;
use Starlit\App\Provider\BootableServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Route;

class BaseAppTest extends TestCase
{
    /**
     * @var BaseApp
     */
    protected $app;

    protected $fakeConfig = ['testkey' => 'testval', 'phpSettings' => ['max_execution_time' => '5001', 'date' => ['timezone' => 'Africa/Kinshasa']]];

    protected $fakeEnv = 'blubb';

    protected function setUp(): void
    {
        $this->app = new BaseApp($this->fakeConfig, $this->fakeEnv);
    }

    /**
     * @covers \Starlit\App\Provider\ErrorServiceProvider::register
     * @covers \Starlit\App\Provider\StandardServiceProvider::register
     */
    public function testInit(): void
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

    public function testBoot(): void
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

    public function testBootCalledTwice()
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
        $this->app->boot();
    }

    public function testHandle(): void
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

    public function testHandleNotFound(): void
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

    public function testHandlePreHandleResponse(): void
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mockBaseApp = (new class($this->fakeConfig, $this->fakeEnv) extends BaseApp {
            protected function preHandle(Request $request)
            {
                return new Response('Pre handle response');
            }
        });
        $response = $mockBaseApp->handle($mockRequest);

        $this->assertEquals('Pre handle response', $response->getContent());
    }

    public function testHandlePostRouteResponse(): void
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $mockRouter = $this->createMock(Router::class);
        $mockController = $this->createMock(\Starlit\App\AbstractController::class);
        $mockBaseApp = (new class($this->fakeConfig, $this->fakeEnv) extends BaseApp {
            protected function postRoute(Request $request)
            {
                return new Response('Post route response');
            }
        });

        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->returnValue($mockController));
        $mockBaseApp->set(RouterInterface::class, $mockRouter);

        $response = $mockBaseApp->handle($mockRequest);

        $this->assertEquals('Post route response', $response->getContent());
    }

    public function testGetValue(): void
    {
        $this->app->set('testKey', new \stdClass());

        $this->assertInstanceOf(\stdClass::class, $this->app->get('testKey'));
    }

    public function testGetValueCall(): void
    {
        $this->app->setSomeKey(new \stdClass());

        $this->assertInstanceOf(\stdClass::class, $this->app->getSomeKey());
    }

    public function testGetFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app->get('invalidKey');
    }

    public function testGetNew(): void
    {
        $this->assertInstanceOf(ViewInterface::class, $this->app->getNew(ViewInterface::class));
    }

    public function testGetNewUndefined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->app->getNew('bla');
    }

    public function testGetNewInvalid(): void
    {
        $this->app->set('someKey', 'someValue');

        $this->expectException(\InvalidArgumentException::class);
        $this->app->getNew('someKey');
    }

    public function testHasInstanceIsTrue(): void
    {
        $this->app->get(ViewInterface::class);
        $this->assertTrue($this->app->hasInstance(ViewInterface::class));
    }

    public function testHasInstanceIsFalse(): void
    {
        $this->assertFalse($this->app->hasInstance('nonExistentObject'));
    }

    public function test__callFail(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->app->setBla();
    }

    public function test__callFail2(): void
    {
        $this->expectException(\BadMethodCallException::class);
        $this->app->fail();
    }

    public function testDestroyInstance(): void
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

    public function testDestroyAllInstances(): void
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

    public function testIsCli(): void
    {
        $this->assertEquals(true, $this->app->isCli());
    }

    public function testGetEnvironment(): void
    {
        $this->assertEquals($this->fakeEnv, $this->app->getEnvironment());
    }

    public function testGetRequestReturnsRequest(): void
    {
        $mockRequest = $this->createMock(Request::class);
        $this->app->set(Request::class, $mockRequest);

        $this->assertSame($mockRequest, $this->app->getRequest());
    }

    public function testHasNoRequest(): void
    {
        $this->assertFalse($this->app->has(Request::class));
    }
}
