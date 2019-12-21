<?php

namespace DetrenaTest\BitrixModuleCore;

use Detrena\BitrixModuleCore\BaseModule;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BaseModuleTest  extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] ?: dirname(dirname(dirname(__DIR__)));
    }

    public function testGetModuleId()
    {
        $this->assertEquals('src', Module::getModuleId());
    }

    public function testGetModuleDir()
    {
        $this->assertEquals($_SERVER['DOCUMENT_ROOT'] . '/src', Module::getModuleDir());
    }

    public function testConfigCacheCreating()
    {
        $container = Module::getContainer();
        // TODO: write correct test
        $this->assertEquals('Detrena\\BitrixModuleCore\\Log\\Logger', get_class($container->get(LoggerInterface::class)));
    }
}

class Module extends BaseModule {
    protected static function getConfigFile()
    {
        return dirname(__DIR__) . "/config.php";
    }
}