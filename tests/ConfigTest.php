<?php

namespace Starlit\App;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $testData = [
        'someValue' => 123,
        'someEmptyValue' => null,
        'someArray' => ['key1' => 456],
        'someEmptyArray' => ['key1' => null, 'key2' => ''],
    ];

    /**
     * @var Config
     */
    private $config;

    protected function setUp()
    {
        $this->config = new Config($this->testData);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->config->has('someValue'));
        $this->assertTrue($this->config->has('someArray'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->config->has('someEmptyValue'));
        $this->assertFalse($this->config->has('someNonExistantValue'));
        $this->assertFalse($this->config->has('someEmptyArray'));
    }

    public function testGetGivesValue()
    {
         $this->assertEquals($this->testData['someValue'], $this->config->get('someValue'));
    }

    public function testGetGivesDefault()
    {
        $this->assertEquals(456, $this->config->get('someEmptyValue', 456));
    }

    public function testGetRequiredGivesValue()
    {
        $this->assertEquals($this->testData['someValue'], $this->config->getRequired('someValue'));
    }

    public function testGetRequiredThrowsException()
    {
        $this->expectException(\RuntimeException::class);
        $this->config->getRequired('someEmptyValue');
    }

    public function testAllReturnsAll()
    {
        $this->assertEquals($this->testData, $this->config->all());
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->config['someValue']));
    }

    public function testOffsetGet()
    {
        $this->assertEquals($this->testData['someValue'], $this->config['someValue']);
    }

    public function testOffsetSet()
    {
        $this->expectException(\LogicException::class);
        $this->config['someValue'] = 789;
    }

    public function testOffsetUnset()
    {
        $this->expectException(\LogicException::class);
        unset($this->config['someValue']);
    }
}

