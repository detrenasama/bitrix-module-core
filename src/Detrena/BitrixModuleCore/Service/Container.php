<?php

namespace Detrena\BitrixModuleCore\Service;

use Detrena\BitrixModuleCore\Exceptions\ServiceFactoryNotCallableException;
use Psr\Container\ContainerInterface;
use Detrena\BitrixModuleCore\Exceptions\ServiceNotFoundException;

class Container implements ContainerInterface {
    protected $services = [];
    protected $cache = [];

    public function __construct($params = [])
    {
        $this->services = $params;
    }

    /**
     * @param string $id
     * @return mixed
     * @throws ServiceNotFoundException
     * @throws ServiceFactoryNotCallableException
     * @throws \ReflectionException
     */
    public function get($id)
    {
        if (array_key_exists($id, $this->cache))
            return $this->cache[$id];

        if (!array_key_exists($id, $this->services)) {
            if (!class_exists($id))
                throw new ServiceNotFoundException('Unknown service "' . $id . '"');

            $reflection = new \ReflectionClass($id);
            $arguments = [];
            if (($constructor = $reflection->getConstructor()) !== null) {
                foreach ($constructor->getParameters() as $parameter) {
                    if ($paramClass = $parameter->getClass()) {
                        $arguments[] = $this->get($paramClass->getName());
                    } elseif ($parameter->isArray()) {
                        $arguments[] = [];
                    } else {
                        if (!$parameter->isDefaultValueAvailable())
                            throw new ServiceNotFoundException('Unable to resolve "' . $parameter->getName() . '" in service "' . $id . '"');

                        $arguments[] = $parameter->getDefaultValue();
                    }
                }
            }
            $this->cache[$id] = $reflection->newInstanceArgs($arguments);
            return $this->cache[$id];
        }

        if ($this->services[$id] instanceof \Closure) {
            $this->cache[$id] = $this->services[$id]($this);

            return $this->cache[$id];
        }

        // Factory class check
        try {
            $reflection = new \ReflectionClass($this->services[$id]);

            /** @var callable $factory */
            $factory = $reflection->newInstance();
            $this->cache[$id] = call_user_func($factory, $this);

            return $this->cache[$id];

        } catch (\ReflectionException $e) {
            // this is not a class

        }

        $this->cache[$id] = $this->services[$id];

        return $this->cache[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->services) || class_exists($id);
    }
}