<?php

namespace Detrena\BitrixModuleCore;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Detrena\BitrixModuleCore\Config\Options;
use Detrena\BitrixModuleCore\Service\Container;

abstract class BaseModule
{
    /** @var Container */
    protected static $container;

    /**
     * @return ContainerInterface
     */
    public static function getContainer()
    {
        if (!static::$container) {
            $config = new ConfigAggregator();

            $cache = static::getConfigCacheFile();
            if (!is_file($cache)) {
                $config->set([
                    $config->file(static::getConfigFile()),
                    $config->file(static::getLocalConfigFile()),
                ]);

                if ($config->get()['cache_config']) {
                    if (!is_dir(dirname($cache)))
                        mkdir(dirname($cache), 0755, true);

                    file_put_contents($cache, '<?php return ' . var_export($config->get(), true) . ';');
                }
            } else {
                $config->set([require($cache)]);

            }

            $configData = $config->get();
            static::$container = new Container($configData);
            if (is_array($configData['dependencies']))
                static::$container->setDependencies($configData['dependencies']);
        }
        return static::$container;
    }

    /**
     * @return Options
     */
    public static function getOptions()
    {
        return static::getContainer()->get(Options::class);
    }

    protected static function getConfigFile()
    {
        return static::getModuleDir() . "/config.php";
    }

    public static function getLocalConfigFile()
    {
        $files = glob($_SERVER['DOCUMENT_ROOT'] . "/{local,bitrix}/config/" . static::getModuleId() . "/config{.cache,}.php", GLOB_BRACE);
        return isset($files[0]) ? $files[0] : '';
    }

    public static function getConfigCacheFile()
    {
        return $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/' . static::getModuleId() . '/config.php';
    }

    /**
     * @return LoggerInterface
     */
    public static function getLogger()
    {
        return static::getContainer()->get(LoggerInterface::class);
    }

    /**
     * @return string
     */
    public static function getModuleId()
    {
        return basename(static::getModuleDir());
    }

    public static function getModuleDir()
    {
        return dirname(dirname(__DIR__));
    }
}