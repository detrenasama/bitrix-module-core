<?php

namespace Vendor\ModuleName\Core;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Vendor\ModuleName\Core\Config\Options;
use Vendor\ModuleName\Core\Service\Container;

abstract class BaseModule
{
	protected static $container;

	/**
	 * @return ContainerInterface
	 */
	public static function getContainer()
	{
		if (!static::$container) {
            $config = new ConfigAggregator();

			$config->set([
				$config->file(static::getConfigFile()),
				$config->file(static::getLocalConfigFile()),
			]);            

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
        return dirname(__DIR__, 2);
	}
}