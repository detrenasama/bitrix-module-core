<?php

namespace DetrenaTest\BitrixModuleCore\Service;

use Detrena\BitrixModuleCore\Service\Container;
use PHPUnit\Framework\TestCase;

class ContainerTest  extends TestCase
{
    public function testGetSimpleValue()
    {
        $value = 'value';
        $container = new Container([
            'simple' => $value
        ]);

        $this->assertEquals($value, $container->get('simple'));
    }

    public function testGetArrayValue()
    {
        $value = [TestClassFactory::class, 'create'];
        $container = new Container([
            TestClass::class => $value
        ]);

        $this->assertEquals($value, $container->get(TestClass::class));
    }

    public function testGetByFactory()
    {
        $value = TestClassFactory::class;
        $container = new Container([

        ]);
        $container->setDependencies([
            'factories' => [
                TestClass::class => $value
            ]
        ]);

        $obj = $container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $obj);
        $this->assertEquals('invoked', $obj->value);
    }

    public function testGetByFactoryMethod()
    {
        $value = [TestClassFactory::class, 'create'];
        $container = new Container([

        ]);
        $container->setDependencies([
            'factories' => [
                TestClass::class => $value
            ]
        ]);

        $obj = $container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $obj);
        $this->assertEquals('created', $obj->value);
    }

    public function testGetByCallable()
    {
        $value = 'DetrenaTest\BitrixModuleCore\Service\TestFunction';
        $container = new Container([

        ]);
        $container->setDependencies([
            'factories' => [
                TestClass::class => $value
            ]
        ]);

        $obj = $container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $obj);
        $this->assertEquals('function', $obj->value);
    }
}

class TestClass {
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class TestClassFactory {
    public function __invoke()
    {
        return new TestClass('invoked');
    }

    public function create()
    {
        return new TestClass('created');
    }
}

function TestFunction() {
    return new TestClass('function');
}