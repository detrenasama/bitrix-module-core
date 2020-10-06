<?php

namespace Vendor\ModuleName\Core;

class ConfigAggregator
{
    protected $config = [];

    /**
     * @param string $filepath
     * @return array
     */
    public function file(string $filepath)
    {
        if (is_file($filepath))
            return (array) @include($filepath);

        return [];
    }

    /**
     * @param array[][] $configs
     */
    public function set(array $configs)
    {
        $configs = array_filter($configs, function ($e) {
            return is_array($e);
        });

        $this->config = $this->merge(...$configs);
    }

    /**
     * @return array
     */
    public function get()
    {
        return $this->config;
    }

    /**
     * @param mixed ...$configs
     * @return array
     */
    protected function merge(...$configs)
    {
        $merged = array();
        foreach ($configs as $config) {
            foreach ($config as $key => $value) {
                if (!array_key_exists($key, $merged)) {
                    $merged[$key] = $value;
                    continue;
                }

                if (is_array($merged[$key])) {

                    if (!is_array($value)) {
                        $merged[$key][] = $value;
                        continue;
                    }

                    if ($this->isList($value)) {
                        $merged[$key] = array_merge($merged[$key], $value);
                        continue;
                    }

                    $merged[$key] = $this->merge($merged[$key], $value);
                    continue;
                }

                if (is_array($value)) {
                    $merged[$key] = $this->merge(array($merged[$key]), $value);
                    continue;
                }

                $merged[$key] = $value;
            }
        }
        return $merged;
    }

    protected function isList(array $value)
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}