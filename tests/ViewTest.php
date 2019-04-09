<?php declare(strict_types=1);

namespace Starlit\App;

use PHPUnit\Framework\TestCase;

class ViewTest extends TestCase
{
    /**
     * @var View
     */
    protected $view;

    protected function setUp(): void
    {
        $this->view = new View(['scriptRootPath' => __DIR__ . '/views']);
    }

    public function testConstructAndOptions(): void
    {
        $fakeOptions = [
            'scriptRootPath' => 'fake/root',
            'fileExtension' => 'fake.php',
        ];
        $tmpObject = new View($fakeOptions);

        $rObject = new \ReflectionObject($tmpObject);

        $prop = $rObject->getProperty('scriptRootPath');
        $prop->setAccessible(true);
        $this->assertEquals($fakeOptions['scriptRootPath'], $prop->getValue($tmpObject));

        $prop = $rObject->getProperty('fileExtension');
        $prop->setAccessible(true);
        $this->assertEquals($fakeOptions['fileExtension'], $prop->getValue($tmpObject));
    }

    public function test__set(): void
    {
       $this->assertEquals('', $this->view->someVar);
       $this->view->someVar = 'blabla';
       $this->assertEquals('blabla', $this->view->someVar);
    }

    public function testRender(): void
    {
       $output = $this->view->render('page');
       $this->assertContains('Page text', $output);
    }

    /**
     * @covers \Starlit\App\View::renderScript
     */
    public function testRenderNonExistantView(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->view->render('non-existant');
    }

    public function testRenderLayout(): void
    {
        $this->view->setLayout('layout');
        $output = $this->view->render('page', true);
        $this->assertContains('Page text', $output);
        $this->assertContains('Layout start', $output);
        $this->assertContains('Layout end', $output);
    }

    public function testEscape(): void
    {
       $this->assertEquals('&amp;', $this->view->escape('&'));
    }

    public function testGetEscaped(): void
    {
        $this->view->someVar = '&';
        $this->assertEquals('&amp;', $this->view->getEscaped('someVar'));
        $this->assertEquals('', $this->view->getEscaped('someNonExistingVar'));
    }

    public function testAddHelperClass(): void
    {
        $mockHelper = $this->createMock(\Starlit\App\ViewHelper\AbstractViewHelper::class);
        $mockClassName = get_class($mockHelper);

        $this->view->addHelperClass('mock', $mockClassName);

        $helper = $this->view->__call('mock');
        $this->assertInstanceOf($mockClassName, $helper);
    }

    public function testGetHelperFail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->view->getHelper('nonExisting');
    }

    public function test__callInvokable(): void
    {
        $this->view->addHelperClass('invokableTestHelper', \Starlit\App\InvokableTestHelper::class);

        $result = $this->view->__call('invokableTestHelper', ['testarg']);
        $this->assertEquals('testarg', $result);
    }

    public function testRequest(): void
    {
        $mockRequest = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $this->view->setRequest($mockRequest);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Request::class, $this->view->getRequest());
    }

    public function test__isset(): void
    {
        $this->assertFalse(isset($this->view->someVar));
        $this->view->someVar = 1;
        $this->assertTrue(isset($this->view->someVar));
    }

    public function testSetLayoutContet(): void
    {
        $content = 'test';
        $this->view->setLayoutContent($content);
        $this->assertEquals($content, $this->view->layoutContent());
    }
}

class InvokableTestHelper extends \Starlit\App\ViewHelper\AbstractViewHelper
{
    public function __invoke(string $parameter): string
    {
        return $parameter;
    }
}
