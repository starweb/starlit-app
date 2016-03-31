<?php

namespace Starlit\App;

class ViewTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var View
     */
    protected $view;

    protected function setUp()
    {
        $this->view = new View(['scriptRootPath' => __DIR__ . '/views']);
    }

    public function testConstructAndOptions()
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

    public function test__set()
    {
       $this->assertEquals('', $this->view->someVar);
       $this->view->someVar = 'blabla';
       $this->assertEquals('blabla', $this->view->someVar);
    }

    public function testRender()
    {
       $output = $this->view->render('page');
       $this->assertContains('Page text', $output);
    }

    /**
     * @covers Starlit\App\View::renderScript
     */
    public function testRenderNonExistantView()
    {
        $this->setExpectedException('\RuntimeException');
        $this->view->render('non-existant');
    }

    public function testRenderLayout()
    {
        $this->view->setLayout('layout');
        $output = $this->view->render('page', true);
        $this->assertContains('Page text', $output);
        $this->assertContains('Layout start', $output);
        $this->assertContains('Layout end', $output);
    }

    public function testEscape()
    {
       $this->assertEquals('&amp;', $this->view->escape('&'));
    }

    public function testGetEscaped()
    {
        $this->view->someVar = '&';
        $this->assertEquals('&amp;', $this->view->getEscaped('someVar'));
        $this->assertEquals('', $this->view->getEscaped('someNonExistingVar'));
    }

    public function testAddHelperClass()
    {
        $mockHelper = $this->getMock('\Starlit\App\ViewHelper\AbstractViewHelper');
        $mockClassName = get_class($mockHelper);

        $this->view->addHelperClass('mock', $mockClassName);

        $helper = $this->view->__call('mock');
        $this->assertInstanceOf($mockClassName, $helper);
    }

    public function testGetHelperFail()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->view->getHelper('nonExisting');
    }

    public function test__callInvokable()
    {
        $this->view->addHelperClass('invokableTestHelper', '\Starlit\App\InvokableTestHelper');

        $result = $this->view->__call('invokableTestHelper', ['testarg']);
        $this->assertEquals('testarg', $result);
    }

    public function testRequest()
    {
        $mockRequest = $this->getMock('\Symfony\Component\HttpFoundation\Request');
        $this->view->setRequest($mockRequest);

        $this->assertInstanceOf('\Symfony\Component\HttpFoundation\Request', $this->view->getRequest());
    }

    public function test__isset()
    {
        $this->assertFalse(isset($this->view->someVar));
        $this->view->someVar = 1;
        $this->assertTrue(isset($this->view->someVar));
    }

    public function testSetLayoutContet()
    {
        $content = 'test';
        $this->view->setLayoutContent($content);
        $this->assertEquals($content, $this->view->layoutContent());
    }
}

class InvokableTestHelper extends \Starlit\App\ViewHelper\AbstractViewHelper
{
    public function __invoke($parameter)
    {
        return $parameter;
    }
}
