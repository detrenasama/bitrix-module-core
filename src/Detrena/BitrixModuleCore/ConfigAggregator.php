<?php

namespace Detrena\BitrixModuleCore;

class ConfigAggregator {
    protected $config = [];
    protected $cache;

    /**
     * @param string $filepath
     * @return array
     */
    public function file($filepath)
    {
        if (is_file($filepath))
            return (array) @include($filepath);

        return [];
    }

    public function set($configs)
    {
        if ($this->cache && is_file($this->cache))
            $this->config = (array) @include($this->cache);
        else
            $this->config = $this->merge(...$configs);
    }

    public function get()
    {
        if (is_file($this->cache))
            $this->config = (array) @include($this->cache);

        return $this->config;
    }

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

                    if (array_keys($value) === range(0, count($value) - 1)) {
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

    public function cache($file)
    {
        $this->cache = $file;
    }
}