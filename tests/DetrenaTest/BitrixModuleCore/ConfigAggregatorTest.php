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

}