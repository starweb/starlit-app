<?php declare(strict_types=1);

namespace Starlit\App;

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
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

    protected function setUp(): void
    {
        $this->config = new Config($this->testData);
    }

    public function testHasIsTrue(): void
    {
        $this->assertTrue($this->config->has('someValue'));
        $this->assertTrue($this->config->has('someArray'));
    }

    public function testHasIsFalse(): void
    {
        $this->assertFalse($this->config->has('someEmptyValue'));
        $this->assertFalse($this->config->has('someNonExistantValue'));
        $this->assertFalse($this->config->has('someEmptyArray'));
    }

    public function testGetGivesValue(): void
    {
         $this->assertEquals($this->testData['someValue'], $this->config->get('someValue'));
    }

    public function testGetGivesDefault(): void
    {
        $this->assertEquals(456, $this->config->get('someEmptyValue', 456));
    }

    public function testGetRequiredGivesValue(): void
    {
        $this->assertEquals($this->testData['someValue'], $this->config->getRequired('someValue'));
    }

    public function testGetRequiredThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->config->getRequired('someEmptyValue');
    }

    public function testAllReturnsAll(): void
    {
        $this->assertEquals($this->testData, $this->config->all());
    }

    public function testOffsetExists(): void
    {
        $this->assertTrue(isset($this->config['someValue']));
    }

    public function testOffsetGet(): void
    {
        $this->assertEquals($this->testData['someValue'], $this->config['someValue']);
    }

    public function testOffsetSet(): void
    {
        $this->expectException(\LogicException::class);
        $this->config['someValue'] = 789;
    }

    public function testOffsetUnset(): void
    {
        $this->expectException(\LogicException::class);
        unset($this->config['someValue']);
    }
}

