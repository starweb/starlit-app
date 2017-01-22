<?php
namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     * @covers \Starlit\App\BaseApp::getSession
     */
    public function testInit()
    {
        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Session\Session', $this->app->getSession());
        $this->assertInstanceOf('\Starlit\App\Router', $this->app->getRouter());
        $this->assertInstanceOf('\Starlit\App\View', $this->app->getView());

        $this->assertEquals($this->fakeConfig['testkey'], $this->app->getConfig()->get('testkey'));
        $this->assertEquals($this->fakeConfig['phpSettings']['max_execution_time'], ini_get('max_execution_time'));
        $this->assertEquals($this->fakeConfig['phpSettings']['date']['timezone'], ini_get('date.timezone'));

        // test setup default routes
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $this->app->getRouter()->getRoutes()->get('/'));
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $this->app->getRouter()->getRoutes()->get('/{action}'));
        $this->assertInstanceOf('Symfony\Component\Routing\Route', $this->app->getRouter()->getRoutes()->get('/{controller}/{action}'));
    }

    public function testHandle()
    {
        $mockRequest = $this->createMock('\Symfony\Component\HttpFoundation\Request');
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockController = $this->createMock('\Starlit\App\AbstractController');

        $mockController->expects($this->once())
            ->method('dispatch')
            ->will($this->returnValue(new Response('respnz')));

        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->returnValue($mockController));
        $this->app->set('router', $mockRouter);


        $response = $this->app->handle($mockRequest);

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals('respnz', $response->getContent());
    }

    public function testHandleNotFound()
    {
        $mockRequest = $this->createMock('\Symfony\Component\HttpFoundation\Request');
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->throwException(new \Symfony\Component\Routing\Exception\ResourceNotFoundException()));
        $this->app->set('router', $mockRouter);

        $response = $this->app->handle($mockRequest);

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Response', $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testHandlePreHandleResponse()
    {
        $mockRequest = $this->createMock('\Symfony\Component\HttpFoundation\Request');

        $mockBaseApp = new TestBaseAppWithPreHandleResponse($this->fakeConfig, $this->fakeEnv);
        $response = $mockBaseApp->handle($mockRequest);

        $this->assertEquals('Pre handle response', $response->getContent());
    }

    public function testHandlePostRouteResponse()
    {
        $mockRequest = $this->createMock('\Symfony\Component\HttpFoundation\Request');
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockController = $this->createMock('\Starlit\App\AbstractController');
        ;
        $mockBaseApp = new TestBaseAppWithPostRouteResponse($this->fakeConfig, $this->fakeEnv);

        $mockRouter->expects($this->once())
            ->method('route')
            ->with($mockRequest)
            ->will($this->returnValue($mockController));
        $mockBaseApp->set('router', $mockRouter);

        $response = $mockBaseApp->handle($mockRequest);

        $this->assertEquals('Post route response', $response->getContent());
    }

    public function testGetValue()
    {
        $this->app->setSomeKey('someValue');

        $this->assertEquals('someValue', $this->app->getSomeKey());
    }

    public function testGetFail()
    {
        $this->expectException('\InvalidArgumentException');
        $this->app->get('invalidKey');
    }

    public function testGetNew()
    {
        $this->assertInstanceOf('\Starlit\App\View', $this->app->getNewView());
    }

    public function testGetNewUndefined()
    {
        $this->expectException('\InvalidArgumentException');
        $this->app->getNew('bla');
    }

    public function testGetNewInvalid()
    {
        $this->app->set('someKey', 'someValue');

        $this->expectException('\InvalidArgumentException');
        $this->app->getNew('someKey');
    }

    public function testHasInstanceIsTrue()
    {
        $this->app->getView();
        $this->assertTrue($this->app->hasInstance('view'));
    }

    public function testHasInstanceIsFalse()
    {
        $this->assertFalse($this->app->hasInstance('nonExistantObject'));
    }

    public function test__callFail()
    {
        $this->expectException('\BadMethodCallException');
        $this->app->setBla();
    }

    public function test__callFail2()
    {
        $this->expectException('\BadMethodCallException');
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
        $this->app->set('request', $mockRequest);

        $this->assertSame($mockRequest, $this->app->getRequest());
    }

    public function testRequestReturnsNull()
    {
        $this->assertNull($this->app->getRequest());
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
