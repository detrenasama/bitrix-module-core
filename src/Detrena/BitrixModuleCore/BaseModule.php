<?php

namespace Detrena\BitrixModuleCore;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Detrena\BitrixModuleCore\Config\Options;

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
			$config->cache($_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/' . static::getModuleId() . '/config.php');
			$config->set([
			    $config->file(static::getConfigFile()),
			    $config->file(static::getLocalConfigFile()),
            ]);

			static::$container = new Container($config->get());
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
	    $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache';

	    if (is_file($file = $cacheDir . '/' . static::getModuleId() . '/config.php'))
	        return $file;

	    return static::getModuleDir() . "/config.php";
	}

	protected static function getLocalConfigFile()
	{
        return glob($_SERVER['DOCUMENT_ROOT'] . "/{local,bitrix}/config/" . static::getModuleId() . "/config{.cache,}.php" ,GLOB_BRACE)[0];
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
	public static function getModuleId() {
	    return basename(static::getModuleDir());
	}

    public static function getModuleDir()
    {
        return dirname(dirname(__DIR__));
	}
}