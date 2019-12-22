<?php

namespace Detrena\BitrixModuleCore\Service;

use Detrena\BitrixModuleCore\Exceptions\ServiceFactoryNotCallableException;
use Psr\Container\ContainerInterface;
use Detrena\BitrixModuleCore\Exceptions\ServiceNotFoundException;

class Container implements ContainerInterface {
    protected $services = [];
    protected $factories = [];
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

        if (array_key_exists($id, $this->factories)) {
            $this->cache[$id] = $this->doFactory($id);
            return $this->cache[$id];
        }

        if (!array_key_exists($id, $this->services)) {
            $this->cache[$id] = $this->doReflection($id);
            return $this->cache[$id];
        }

        $this->cache[$id] = $this->services[$id];
        return $this->cache[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->services) || class_exists($id);
    }

    public function setDependencies(array $dependencies)
    {
        if (is_array($dependencies['factories']))
            $this->factories = $dependencies['factories'];

    }
    /**
     * @param string $id
     * @return mixed
     * @throws ServiceFactoryNotCallableException
     */
    protected function doFactory($id)
    {
        if (is_array($this->factories[$id]) && is_callable($this->factories[$id]))
            return $this->factories[$id]($this);

        if (is_callable($this->factories[$id]))
            return $this->factories[$id]($this);

        try {
            $reflection = new \ReflectionClass($this->factories[$id]);

            /** @var callable $factory */
            $factory = $reflection->newInstance();
            return call_user_func($factory, $this);

        } catch (\ReflectionException $e) {
            // ID is not a class
            throw new ServiceFactoryNotCallableException("Factory '".var_export($this->factories[$id],true)."' is not found or not callable!");
        }
    }

    /**
     * @param $id
     * @return object
     * @throws ServiceFactoryNotCallableException
     * @throws ServiceNotFoundException
     * @throws \ReflectionException
     */
    protected function doReflection($id)
    {
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

        return $reflection->newInstanceArgs($arguments);
    }
}