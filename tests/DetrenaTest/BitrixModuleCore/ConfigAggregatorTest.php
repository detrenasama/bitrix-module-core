<?php

namespace DetrenaTest\BitrixModuleCore;

use Detrena\BitrixModuleCore\ConfigAggregator;
use PHPUnit\Framework\TestCase;

class ConfigAggregatorTest extends TestCase
{

    public function testFileMethodReturnsArray()
    {
        $filePath = __DIR__ . '/config.php';
        $configArray = [
            'key' => 'value'
        ];
        $fileContent = "<?php return ".var_export($configArray,true).";";

        $config = new ConfigAggregator();

        $this->assertEquals([], $config->file($filePath));

        file_put_contents($filePath, $fileContent);

        $this->assertEquals($configArray, $config->file($filePath));

        unlink($filePath);

        $this->assertEquals([], $config->file($filePath));
    }

    public function testSetSimpleValue()
    {
        $example = [
            'simple' => 'value'
        ];

        $config = new ConfigAggregator();
        $config->set([
            $example
        ]);

        $this->assertEquals($example, $config->get());
    }

    public function testMergeSimpleValue()
    {
        $example1 = [
            'one' => 'value1',
            'two' => 'value2',
        ];

        $example2 = [
            'one' => 'value3',
            'three' => 'value4',
        ];

        $expected = [
            'one' => 'value3',
            'two' => 'value2',
            'three' => 'value4',
        ];

        $config = new ConfigAggregator();
        $config->set([
            $example1,
            $example2
        ]);

        $this->assertEquals($expected, $config->get());
    }

    public function testSetSimpleList()
    {
        $example = [
            'list' => [
                'first',
                'two',
                'three'
            ]
        ];

        $config = new ConfigAggregator();
        $config->set([
            $example
        ]);

        $this->assertEquals($example, $config->get());
    }

    public function testMergeSimpleList()
    {
        $example1 = [
            'list' => [
                'one',
                'two',
                'three'
            ]
        ];

        $example2 = [
            'list' => [
                'four',
                'two',
            ]
        ];

        $expected = [
            'list' => [
                'one',
                'two',
                'three',
                'four',
                'two'
            ]
        ];

        $config = new ConfigAggregator();
        $config->set([
            $example1,
            $example2
        ]);

        $this->assertEquals($expected, $config->get());
    }

    public function testSetAssociativeList()
    {
        $example = [
            'list' => [
                'one' => 'first',
                'second' => 'two',
                'foo' => 'three'
            ]
        ];

        $config = new ConfigAggregator();
        $config->set([
            $example
        ]);

        $this->assertEquals($example, $config->get());
    }

    public function testMergeAssociativeList()
    {
        $example1 = [
            'list' => [
                'one' => 'value1',
                'two' => 'value2',
                'three' => 'value3'
            ]
        ];

        $example2 = [
            'list' => [
                'four' => 'value4',
                'two' => 'value5',
            ]
        ];

        $expected = [
            'list' => [
                'one' => 'value1',
                'two' => 'value5',
                'three' => 'value3',
                'four' => 'value4',
            ]
        ];

        $config = new ConfigAggregator();
        $config->set([
            $example1,
            $example2
        ]);

        $this->assertEquals($expected, $config->get());
    }

}