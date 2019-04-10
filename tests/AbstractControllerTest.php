<?php declare(strict_types=1);
namespace Starlit\App;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ServerBag;

class AbstractControllerTest extends TestCase
{
    /**
     * @var AbstractController
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mockRouter;

    protected function setUp(): void
    {
        $this->mockApp = $this->createMock(BaseApp::class);
        $this->mockRequest = $this->createMock(Request::class);
        $this->mockRequest->server = $this->createMock(ServerBag::class);
        $this->mockRequest->attributes = $this->createMock(ParameterBag::class);

        $this->mockView = $this->createMock(View::class);
        $this->mockResponse = new Response();
        $this->mockRouter = null;

        $this->mockApp->method('getNew')
            ->willReturn($this->mockView);
        $this->mockApp->method('get')
            ->willReturnCallback(
                function ($className) {
                    switch ($className) {
                        case Response::class:
                            return $this->mockResponse;
                        case RouterInterface::class:
                            return $this->mockRouter;
                    }
                }
            );
        $this->mockApp->method('resolveParameters')
            ->willReturnCallback(
                function ($params, $predefinedValues = []) {
                    if ($predefinedValues) {
                        if (!isset($predefinedValues['someParam'], $predefinedValues['otherParam'])) {
                            throw new \ReflectionException();
                        }

                        return [$predefinedValues['someParam'], $predefinedValues['otherParam'], 'wow'];
                    }

                    return [];
                }
            );

        $this->testController = $this->getTestController();
    }

    public function testConstruct(): void
    {
        // check view
        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('view');
        $prop->setAccessible(true);
        $this->assertInstanceOf(\Starlit\App\View::class, $prop->getValue($this->testController));
    }

    public function testSetAutoRenderView(): void
    {
        $this->testController->setAutoRenderView(false);

        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('autoRenderView');
        $prop->setAccessible(true);

        $this->assertFalse($prop->getValue($this->testController));
    }

    public function testSetAutoRenderViewScript(): void
    {
        $this->testController->setAutoRenderViewScript('someScript');

        $rObject = new \ReflectionObject($this->testController);
        $prop = $rObject->getProperty('autoRenderViewScript');
        $prop->setAccessible(true);

        $this->assertEquals('someScript', $prop->getValue($this->testController));
    }

    public function testDispatch(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->method('getActionMethod')
            ->with('index')
            ->willReturn('indexAction');
        $this->mockRouter->method('getRequestController')
            ->willReturn('test');

        // mock View::render return
        $this->mockView->expects($this->atLeastOnce())
            ->method('render')
            ->with('test/index')
            ->willReturn('yes');


        $response = $this->testController->dispatch('index');
        $this->assertEquals('yes', $response->getContent());
    }

    public function testDispatchPreDispatch(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->method('getActionMethod')
            ->with('pre-test')
            ->will($this->returnValue('preTestAction'));

        $response = $this->testController->dispatch('pre-test');
        $this->assertEquals('preOk', $response->getContent());
    }

    public function testDispatchSpecifiedWithResponseAndParamAction(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('some-other')
            ->will($this->returnValue('someOtherAction'));

        // mock request-attributes has
        $this->mockRequest->attributes->expects($this->once())
            ->method('all')
            ->will($this->returnValue(['someParam' => 'ooh']));

        $response = $this->testController->dispatch('some-other', ['otherParam' => 'aaa']);
        $this->assertEquals('ooh aaa wow', $response->getContent());
    }

    public function testDispatchWithoutReqParam(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('some-other')
            ->will($this->returnValue('someOtherAction'));

        // mock request-attributes has
        $this->mockRequest->attributes->expects($this->once())
            ->method('all')
            ->will($this->returnValue(['someParam' => 'ooh']));

        $this->expectException(\LogicException::class);
        $this->testController->dispatch('some-other');
    }

    public function testDispatchNonExistingAction(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('none')
            ->will($this->returnValue('noneAction'));

        $this->expectException(\Symfony\Component\Routing\Exception\ResourceNotFoundException::class);
        $this->testController->dispatch('none');
    }

    public function testDispatchInvalidAction(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('invalid')
            ->will($this->returnValue('invalidAction'));

        $this->expectException(\Symfony\Component\Routing\Exception\ResourceNotFoundException::class);
        $this->testController->dispatch('invalid');
    }

    public function testDispatchNoAutoAction(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('no-auto')
            ->will($this->returnValue('noAutoAction'));

        $response = $this->testController->dispatch('no-auto');
        $this->assertEquals('', $response->getContent());
    }

    public function testDispatchStringReturn(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('string-return')
            ->will($this->returnValue('stringReturnAction'));

        $response = $this->testController->dispatch('string-return');
        $this->assertEquals('a string', $response->getContent());
    }

    public function testForwardInternal(): void
    {
        // mock Router::getActionMethod return
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));


        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testForward(): void
    {
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getRequestModule')
            ->willReturn(null);
        $this->mockRouter->expects($this->once())
            ->method('getControllerClass')
            ->with('login')
            ->will($this->returnValue(\get_class($this->testController)));
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));

        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end', 'login']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testForwardWithModule(): void
    {
        $this->mockRouter = $this->createMock(Router::class);
        $this->mockRouter->expects($this->once())
            ->method('getControllerClass')
            ->with('login', 'admin')
            ->will($this->returnValue(\get_class($this->testController)));
        $this->mockRouter->expects($this->once())
            ->method('getActionMethod')
            ->with('forward-end')
            ->will($this->returnValue('forwardEndAction'));


        // Gain access to protected forward method
        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('forward');
        $method->setAccessible(true);

        $response = $method->invokeArgs($this->testController, ['forward-end', 'login', 'admin']);
        $this->assertEquals('eeend', $response->getContent());
    }

    public function testGetUrlNoUrl(): void
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

    public function testGetUrl(): void
    {
        $this->mockRequest->expects($this->any())
            ->method('getSchemeAndHttpHost')
            ->will($this->returnValue('http://www.example.org'));

        $this->mockRequest->query = $this->createMock(ParameterBag::class);
        $this->mockRequest->query->expects($this->once())
            ->method('all')
            ->will($this->returnValue(['a' => 1]));

        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('getUrl');
        $method->setAccessible(true);

        $this->assertEquals('http://www.example.org/hej/hopp?a=1&b=2', $method->invokeArgs($this->testController, ['/hej/hopp', ['b' => '2']]));
    }

    public function testGet(): void
    {
        $get = ['a' => 1, 'b' => 2];
        $this->mockRequest->query = $this->createMock(ParameterBag::class);
        $this->mockRequest->query->expects($this->once())
            ->method('all')
            ->will($this->returnValue($get));
        $this->mockRequest->query->expects($this->once())
            ->method('get')
            ->will($this->returnValue($get['a']));


        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('get');
        $method->setAccessible(true);

        $this->assertEquals($get, $method->invokeArgs($this->testController, []));
        $this->assertEquals($get['a'], $method->invokeArgs($this->testController, ['a']));
    }

    public function testPost(): void
    {
        $get = ['a' => 1, 'b' => 2];
        $this->mockRequest->request = $this->createMock(ParameterBag::class);
        $this->mockRequest->request->expects($this->once())
            ->method('all')
            ->will($this->returnValue($get));
        $this->mockRequest->request->expects($this->once())
            ->method('get')
            ->will($this->returnValue($get['a']));


        $rObject = new \ReflectionObject($this->testController);
        $method = $rObject->getMethod('post');
        $method->setAccessible(true);

        $this->assertEquals($get, $method->invokeArgs($this->testController, []));
        $this->assertEquals($get['a'], $method->invokeArgs($this->testController, ['a']));
    }

    protected function getTestController(): AbstractController
    {
        return (new class($this->mockApp, $this->mockRequest) extends AbstractController {
            public function indexAction(): void
            {
            }

            public function someOtherAction($someParam, $otherParam, $paramWithDefault = 'wow'): Response
            {
                return new Response($someParam . ' ' . $otherParam . ' ' . $paramWithDefault);
            }

            protected function invalidAction(): void
            {
            }

            public function noAutoAction(): void
            {
                $this->setAutoRenderView(false);
            }

            public function forwardEndAction(): Response
            {
                return new Response('eeend');
            }

            public function preTestAction() { }

            protected function preDispatch($action): ?Response
            {
                if ($action === 'pre-test') {
                    return new Response('preOk');
                }

                return parent::preDispatch($action); // For code coverage...
            }

            public function stringReturnAction(): string
            {
                return 'a string';
            }
        });
    }
}
