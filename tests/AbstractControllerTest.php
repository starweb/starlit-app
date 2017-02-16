<?php
namespace Starlit\App;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;

class AbstractControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TestController
     */
    protected $testController;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockApp;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRequest;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockView;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockResponse;

    protected function setUp()
    {
        $this->mockApp = $this->createMock(BaseApp::class);
        $this->mockRequest = $this->createMock(Request::class);
        $this->mockRequest->server = $this->createMock(ServerBag::class);
        $this->mockRequest->attributes = $this->createMock(ParameterBag::class);

        $this->mockView = $this->createMock(View::class);
        $this->mockApp->expects($this->any())
            ->method('getNew')
            ->with('view')
            ->will($this->returnValue($this->mockView));

        $this->mockResponse = new Response();
        $this->mockApp->expects($this->any())
            ->method('get')
            ->with('response')
            ->will($this->returnValue($this->mockResponse));

        $this->testController = new TestController($this->mockApp, $this->mockRequest);
    }

    public function testConstruct()
    {
        // check view
        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('view');
        $prop->setAccessible(true);
        $this->assertInstanceOf('\Starlit\App\View', $prop->getValue($this->testController));
    }

    public function testSetAutoRenderView()
    {
        $this->testController->setAutoRenderView(false);

        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('autoRenderView');
        $prop->setAccessible(true);

        $this->assertFalse($prop->getValue($this->testController));
    }

    public function testSetAutoRenderViewScript()
    {
        $this->testController->setAutoRenderViewScript('someScript');

        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('autoRenderViewScript');
        $prop->setAccessible(true);

        $this->assertEquals('someScript', $prop->getValue($this->testController));
    }

    public function testDispatch()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->method('getActionMethod')
            ->with('index')
            ->willReturn('indexAction');
        $mockRouter->method('getRequestController')
            ->willReturn('test');
        $this->mockApp->method('getRouter')
            ->willReturn($mockRouter);

        // mock View::render return
        $this->mockView->expects($this->atLeastOnce())
            ->method('render')
            ->with('test/index')
            ->willReturn('yes');


        $response = $this->testController->dispatch('index');
        $this->assertEquals('yes', $response->getContent());
    }

    public function testDispatchPreDispatch()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->method('getActionMethod')
            ->with('pre-test')
            ->will($this->returnValue('preTestAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $response = $this->testController->dispatch('pre-test');
        $this->assertEquals('preOk', $response->getContent());
    }

    public function testDispatchSpecifiedWithResponseAndParamAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('some-other')
            ->will($this->returnValue('someOtherAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        // mock request-attributes has
        $this->mockRequest->attributes->expects($this->exactly(2))
            ->method('has')
            ->will($this->returnCallback(function ($paramName) {
                return ($paramName == 'someParam');
            }));
        $this->mockRequest->attributes->expects($this->once())
            ->method('get')
            ->with('someParam')
            ->will($this->returnValue('ooh'));



        $response = $this->testController->dispatch('some-other', ['otherParam' => 'aaa']);
        $this->assertEquals('ooh aaa wow', $response->getContent());

    }

    public function testDispatchWithoutReqParam()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('some-other')
            ->will($this->returnValue('someOtherAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        // mock request-attributes has
        $this->mockRequest->attributes->expects($this->once())
            ->method('has')
            ->will($this->returnValue(false));

        $this->expectException('\LogicException');
        $this->testController->dispatch('some-other');
    }

    public function testDispatchNonExistingAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('none')
            ->will($this->returnValue('noneAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $this->expectException('\Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $this->testController->dispatch('none');
    }

    public function testDispatchInvalidAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('invalid')
            ->will($this->returnValue('invalidAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $this->expectException('\Symfony\Component\Routing\Exception\ResourceNotFoundException');
        $this->testController->dispatch('invalid');
    }

    public function testDispatchNoAutoAction()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('no-auto')
            ->will($this->returnValue('noAutoAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $response = $this->testController->dispatch('no-auto');
        $this->assertEquals('', $response->getContent());
    }

    public function testDispatchStringReturn()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('string-return')
            ->will($this->returnValue('stringReturnAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        $response = $this->testController->dispatch('string-return');
        $this->assertEquals('a string', $response->getContent());
    }

    public function testForwardInternal()
    {
        // mock Router::getActionMethod return
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));


        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testForward()
    {
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getRequestModule')
            ->willReturn(null);
        $mockRouter->expects($this->once())
            ->method('getControllerClass')
            ->with('login')
            ->will($this->returnValue('Starlit\App\TestController'));
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));

        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end', 'login']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testForwardWithModule()
    {
        $mockRouter = $this->createMock('\Starlit\App\Router');
        $mockRouter->expects($this->once())
            ->method('getControllerClass')
            ->with('login', 'admin')
            ->will($this->returnValue('Starlit\App\TestController'));
        $mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));
        $this->mockApp->method('getRouter')
            ->will($this->returnValue($mockRouter));


        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end', 'login', 'admin']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testGetUrlNoUrl()
    {
        $this->mockRequest->expects($this->any())
            ->method('getSchemeAndHttpHost')
            ->will($this->returnValue('http://www.example.org'));

        $this->mockRequest->expects($this->any())
            ->method('getRequestUri')
            ->will($this->returnValue('/hej/hopp'));

        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('getUrl');
        $method->setAccessible(true);

        $this->assertEquals('http://www.example.org/hej/hopp', $method->invokeArgs($this->testController, []));
    }

    public function testGetUrl()
    {
        $this->mockRequest->expects($this->any())
            ->method('getSchemeAndHttpHost')
            ->will($this->returnValue('http://www.example.org'));

        $this->mockRequest->query = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->query->expects($this->exactly(1))
            ->method('all')
            ->will($this->returnValue(['a' => 1]));

        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('getUrl');
        $method->setAccessible(true);

        $this->assertEquals('http://www.example.org/hej/hopp?a=1&b=2', $method->invokeArgs($this->testController, ['/hej/hopp', ['b' => '2']]));
    }

    public function testGet()
    {
        $get = ['a' => 1, 'b' => 2];
        $this->mockRequest->query = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->query->expects($this->exactly(1))
            ->method('all')
            ->will($this->returnValue($get));
        $this->mockRequest->query->expects($this->exactly(1))
            ->method('get')
            ->will($this->returnValue($get['a']));


        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('get');
        $method->setAccessible(true);

        $this->assertEquals($get, $method->invokeArgs($this->testController, []));
        $this->assertEquals($get['a'], $method->invokeArgs($this->testController, ['a']));
    }

    public function testPost()
    {
        $get = ['a' => 1, 'b' => 2];
        $this->mockRequest->request = $this->createMock('\Symfony\Component\HttpFoundation\ParameterBag');
        $this->mockRequest->request->expects($this->exactly(1))
            ->method('all')
            ->will($this->returnValue($get));
        $this->mockRequest->request->expects($this->exactly(1))
            ->method('get')
            ->will($this->returnValue($get['a']));


        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('post');
        $method->setAccessible(true);

        $this->assertEquals($get, $method->invokeArgs($this->testController, []));
        $this->assertEquals($get['a'], $method->invokeArgs($this->testController, ['a']));
    }
}

class TestController extends AbstractController
{

    public function indexAction()
    {
    }

    public function someOtherAction($someParam, $otherParam, $paramWithDefault = 'wow')
    {
        return new Response($someParam . ' ' . $otherParam . ' ' . $paramWithDefault);
    }

    protected function invalidAction()
    {
    }

    public function noAutoAction()
    {
        $this->setAutoRenderView(false);
    }

    public function forwardEndAction()
    {
        return new Response('eeend');
    }

    public function preTestAction() { }

    protected function preDispatch($action)
    {
        parent::preDispatch($action); // For code coverage...

        if ($action === 'pre-test') {
            return new Response('preOk');
        }
    }

    public function stringReturnAction()
    {
        return 'a string';
    }

}
