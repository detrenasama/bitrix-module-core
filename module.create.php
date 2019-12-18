<?php

namespace IO {
	class IO {
		static function Ask($message, &$var) {
			if (!is_null($var)) {
				echo "$message ({$var}): ";
			} else {
				echo "$message: ";
			}

			try {
				$line = trim(fgets(STDIN));

				$var = strlen($line) ? $line : $var;
			} catch (\Exception $e) {
				self::Say("Error: %s", $e->getMessage());
			}
		}

		static function Say($message, $a1 = null, $a2 = null, $a3 = null, $a4 = null, $a5 = null) {
			printf("$message\n", $a1, $a2, $a3, $a4, $a5);
		}
	}

	class Filesystem {
		static function CreateFile($path, $content) {
			if (!file_exists(dirname($path))) {
				mkdir(dirname($path), 0777, true);
			}
			file_put_contents($path, $content);
		}
	}

	class Progress {
		static $progress = null;

		static function ProgressStart($message, $value = '0%') {
			static::$progress = $message;
			self::ProgressUpdate($value);
		}
		static function ProgressEnd() {
			if (static::$progress) {
				static::$progress = null;
				fwrite(STDOUT, "\n");
			}
		}
		static function ProgressUpdate($value) {
			$mes = substr(static::$progress, 0, TERMINAL_WIDTH - strlen($value) - 1);
			$whitespace = str_repeat(".", TERMINAL_WIDTH - strlen($mes) - strlen($value) - 1);
			fwrite(STDOUT, "\r{$mes}{$whitespace} {$value}");
		}
	}
}

namespace {
	use IO\IO;
	use IO\Filesystem;

	if ($_SERVER['DOCUMENT_ROOT'])
		die("CLI only");

	class Template {
		private $params = [];

		public function __construct($params = [])
		{
			$this->params = $params;
		}

		public function render($content)
		{
			return preg_replace_callback('/{{[^}]+}}/', [$this, 'interpolate'], $content);
		}

		private function interpolate($template)
		{
			if (is_array($template))
				$template = reset($template);

			$filters = array_map(function ($e) {
				return trim($e);
			}, explode('|', trim($template, '{}')));

			$var = array_shift($filters);

			$value = $this->arrayValue($var, $this->params);

			if (!empty($filters)) {
				foreach ($filters as $filter) {
					if (!method_exists($this, "filter_{$filter}"))
						throw new \Exception("Filter '{$filter}' not found!");

					$value = call_user_func([$this, "filter_{$filter}"], $value);
				}
			}

			return $value;
		}

		/**
		 * @param $var
		 * @param $param
		 * @return mixed
		 * @throws Exception
		 */
		private function arrayValue($var, $param) {
			if (is_null($var))
				return $param;

			$shiftedKeys = explode('.', $var, 2);
			$key = array_key_exists(0, $shiftedKeys) ? $shiftedKeys[0] : null;
			$more = array_key_exists(1, $shiftedKeys) ? $shiftedKeys[1] : null;

			if (is_array($param) && array_key_exists($key, $param))
				return $this->arrayValue($more, $param[$key]);

			throw new \Exception(sprintf("arrayValue error: Cannot reach '%s' in %s", $var, var_export($param, true)));
		}

		public function filter_slashed($value)
		{
			return addslashes($value);
		}
	}


	$arTemplate = [];

	$arTemplate['/src/Core/Config/Options.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Config;

use Bitrix\Main\Config\Option;

class Options {
    protected \$moduleId;

    public function __construct(\$moduleId)
    {
        \$this->moduleId = \$moduleId;
    }

	public function defaults()
	{
		return Option::getDefaults(\$this->moduleId);
	}
	public function real()
	{
		return Option::getForModule(\$this->moduleId);
	}
    public function get(\$name, \$default = null, \$siteId = false)
    {
        return \$this->dbValue(Option::get(\$this->moduleId, \$name, \$default, \$siteId));
    }
    public function set(\$name, \$value = '', \$siteId = '')
    {
        return Option::set(\$this->moduleId, \$name, \$value, \$siteId);
    }
	public function delete(array \$filter = [])
	{
		return Option::delete(\$this->moduleId, \$filter);
	}


	public function all()
	{
		\$defaults = \$this->defaults();
		\$real = \$this->real();
		\$options = array_merge(\$defaults, \$real);
		return array_map([\$this, 'dbValue'], \$options);
	}

	public function save(\$arOptions)
	{
		\$options = \$this->defaults();
		foreach (\$options as \$key => \$value) {
			if (isset(\$arOptions[\$key]))
				\$this->set(\$this->safeKey(\$key), \$this->safeValue(\$arOptions[\$key]));
			else
				\$this->set(\$this->safeKey(\$key), \$this->safeValue(\$value));
		}
    }

	protected function safeKey(\$key)
	{
		if (strlen(\$key) > 40)
			throw new \InvalidArgumentException("{key} length must be less or equal 40");

		return preg_replace('/[^\d\w\_]/' , '_', \$key);
    }

	protected function safeValue(\$value)
	{
		if (is_object(\$value))
			throw new \InvalidArgumentException("{value} cannot be an object!");

		if (is_array(\$value))
			return serialize(\$value);

		return \$value;
    }

	protected function dbValue(\$value)
	{
		if (is_string(\$value) && is_array(\$res = unserialize(\$value)))
			return \$res;

		return \$value;
    }
}
PHP;
	$arTemplate['/src/Core/Exceptions/ServiceNotFoundException.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class ServiceNotFoundException extends \Exception implements NotFoundExceptionInterface {}
PHP;

	$arTemplate['/src/Core/Log/FileLogHandler.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Log;

class FileLogHandler implements LogHandlerInterface {
    protected \$file;

    /** @var resource */
    protected \$stream;
    protected \$template = "{time} [{level}]: {message}\n";

    public function __construct(\$file)
    {
        \$this->file = (string) \$file;
    }

    public function setTemplate(\$template)
    {
        \$this->template = \$template;
    }

    /**
     * @param \$level
     * @param \$message
     * @param array \$context
     * @throws \Exception
     */
    public function log(\$level, \$message, array \$context = array())
    {
        if (!\$this->stream) {
            if (!file_exists(dirname(\$this->file)))
                mkdir(dirname(\$this->file), 0755, true);

            \$this->stream = fopen(\$this->file, "a+");
            if (!\$this->stream)
                throw new \Exception(sprintf("Cannot write into '%s'!", \$this->file));
        }

        if (\$this->stream)
            \$this->write(\$level, \$message, \$context);
    }

    protected function write(\$level, \$message, array \$context = array())
    {
        fwrite(\$this->stream, \$this->interpolate(\$this->template, array(
            'time' => date("Y-m-d H:i:s"),
            'level' => \$level,
            'message' => \$this->interpolate(\$message, \$context),
        )));
    }

    protected function interpolate(\$message, array \$context = array())
    {
        \$replace = array();
        foreach (\$context as \$key => \$val) {
            if (!is_array(\$val) && (!is_object(\$val) || method_exists(\$val, '__toString'))) {
                \$replace['{' . \$key . '}'] = \$val;
            } else {
                \$replace['{' . \$key . '}'] = var_export(\$val,true);
            }
        }

        return strtr(\$message, \$replace);
    }

    public function __destruct()
    {
        if (\$this->stream)
            fclose(\$this->stream);
    }
}
PHP;
	$arTemplate['/src/Core/Log/Logger.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface {
    protected \$levelMap = array(
        LogLevel::NOTICE => 1,
        LogLevel::INFO => 2,
        LogLevel::WARNING => 3,
        LogLevel::ALERT => 4,
        LogLevel::ERROR => 5,
        LogLevel::CRITICAL => 6,
        LogLevel::EMERGENCY => 7,
        LogLevel::DEBUG => 8,
    );

    protected \$level;
    /** @var LogHandlerInterface */
    protected \$handler;

    public function setLogHandler(LogHandlerInterface \$handler)
    {
        \$this->handler = \$handler;
    }

    public function setLevel(\$level)
    {
        \$this->level = \$level;
    }

    public function emergency(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::EMERGENCY])
            \$this->log(LogLevel::EMERGENCY, \$message, \$context);
    }
    public function alert(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::ALERT])
            \$this->log(LogLevel::ALERT, \$message, \$context);
    }
    public function critical(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::CRITICAL])
            \$this->log(LogLevel::CRITICAL, \$message, \$context);
    }
    public function error(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::ERROR])
            \$this->log(LogLevel::ERROR, \$message, \$context);
    }
    public function warning(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::WARNING])
            \$this->log(LogLevel::WARNING, \$message, \$context);
    }
    public function notice(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::NOTICE])
            \$this->log(LogLevel::NOTICE, \$message, \$context);
    }
    public function info(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::INFO])
            \$this->log(LogLevel::INFO, \$message, \$context);
    }
    public function debug(\$message, array \$context = array())
    {
        if (\$this->level && \$this->levelMap[\$this->level] >= \$this->levelMap[LogLevel::DEBUG])
            \$this->log(LogLevel::DEBUG, \$message, \$context);
    }

    public function log(\$level, \$message, array \$context = array())
    {
        if (\$this->handler)
            \$this->handler->log(\$level, \$message, \$context);
    }
}
PHP;
	$arTemplate['/src/Core/Log/LogHandlerInterface.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core\Log;

interface LogHandlerInterface {
    public function log(\$level, \$message, array \$context = array());
}
PHP;

	$arTemplate['/src/Core/Installer.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;

abstract class Installer extends \CModule
{
    public \$MODULE_ID;
    public \$MODULE_VERSION;
    public \$MODULE_VERSION_DATE;
    public \$MODULE_NAME;
    public \$MODULE_DESCRIPTION;
    public \$MODULE_GROUP_RIGHTS;

    public \$PARTNER_NAME;
    public \$PARTNER_URI;

    public function __construct()
    {
        \$dir = \$this->getModuleDir();
        \$this->MODULE_ID = basename(\$dir);

        \$arModuleVersion = array();
        include(\$dir . "/install/version.php");
        \$this->MODULE_VERSION = \$arModuleVersion["VERSION"];
        \$this->MODULE_VERSION_DATE = \$arModuleVersion["VERSION_DATE"];
    }

    public function DoInstall()
    {
        ModuleManager::registerModule(\$this->MODULE_ID);
        \$this->InstallFiles();
    }

    public function DoUninstall()
    {
        \$this->UnInstallFiles();
        ModuleManager::unregisterModule(\$this->MODULE_ID);
    }

    public function InstallFiles()
    {
        // Components path mask: MODULE_PATH/install/components/MODULE_NAME.COMPONENT_NAME
        // Where MODULE_NAME in module '[vendor].[module]' is '[module]'

        \$components = \$this->GetModuleDir()."/install/components";
        if (Directory::isDirectoryExists(\$components)){
            CopyDirFiles(\$components, Application::getDocumentRoot() . "/bitrix/components/{\$this->getVendor()}/", true, true);
        }

        // Directories will be copied in /bitrix/DIRECTORY_NAME/MODULE_ID/

        \$files = glob(\$this->GetModuleDir()."/install/files/*/");
        foreach (\$files as \$dir) {
            \$basename = basename(\$dir);
            CopyDirFiles(\$dir, Application::getDocumentRoot() . "/bitrix/{\$basename}/{\$this->MODULE_ID}/", true, true);
        }
    }

    public function UnInstallFiles()
    {
        \$components = glob(Application::getDocumentRoot()."/bitrix/components/{\$this->getVendor()}/{\$this->getModuleCode()}.*/");
        foreach (\$components as \$dir)
            Directory::deleteDirectory(\$dir);

        \$files = glob(\$this->GetModuleDir()."/install/files/*/");
        foreach (\$files as \$dir) {
            \$basename = basename(\$dir);
            Directory::deleteDirectory(Application::getDocumentRoot()."/bitrix/{\$basename}/{\$this->MODULE_ID}");
        }
    }

    /**
     * @return string
     */
    private function getModuleDir()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * @return string
     */
    private function getVendor()
    {
        return (string) substr(\$this->MODULE_ID, 0, strpos(\$this->MODULE_ID, '.'));
    }

    /**
     * @return string
     */
    private function getModuleCode()
    {
        return (string) substr(\$this->MODULE_ID, strpos(\$this->MODULE_ID, '.')+1);
    }
}
PHP;
	$arTemplate['/src/Core/BaseModule.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core;

use Psr\Container\ContainerInterface;
use {{ module.namespace }}\Core\Config\Options;
use Bitrix\Main\Loader;

abstract class BaseModule
{
	protected static \$container;

	/**
	 * @return ContainerInterface
	 */
	public static function getContainer()
	{
		if (!static::\$container) {
			\$baseConfig = file_exists(static::getConfigFile()) ? require(static::getConfigFile()) : [];
			\$localConfig = file_exists(static::getLocalConfigFile()) ? require(static::getLocalConfigFile()) : [];
			\$config = static::mergeConfigs(\$baseConfig, \$localConfig);

			static::\$container = new Container(\$config);
		}
		return static::\$container;
	}

	protected static function mergeConfigs(...\$configs)
	{
		\$merged = array();
		foreach (\$configs as \$config) {
			foreach (\$config as \$key => \$value) {
				if (!array_key_exists(\$key, \$merged)) {
					\$merged[\$key] = \$value;
					continue;
				}

				if (is_array(\$merged[\$key])) {

					if (!is_array(\$value)) {
						\$merged[\$key][] = \$value;
						continue;
					}

					if (array_keys(\$value) === range(0, count(\$value) - 1)) {
						\$merged[\$key] = array_merge(\$merged[\$key], \$value);
						continue;
					}

					\$merged[\$key] = static::mergeConfigs(\$merged[\$key], \$value);
					continue;
				}

				if (is_array(\$value)) {
					\$merged[\$key] = static::mergeConfigs(array(\$merged[\$key]), \$value);
					continue;
				}

				\$merged[\$key] = \$value;
			}
		}
		return \$merged;
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
		return Loader::getLocal('modules/' . static::getModuleId() . '/config.php');
	}

	protected static function getCustomConfigFile()
	{
		return Loader::getLocal('config/' . static::getModuleId() . '/config.php');
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
	    return dirname(dirname(__DIR__));
	}
}
PHP;
	$arTemplate['/src/Core/Container.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Core;

use Psr\Container\ContainerInterface;
use {{ module.namespace }}\Core\Exceptions\ServiceNotFoundException;

class Container implements ContainerInterface {
    protected \$services = [];
    protected \$cache = [];

    public function __construct(\$params = [])
    {
        \$this->services = \$params;
    }

    /**
     * @param string \$id
     * @return mixed
     * @throws ServiceNotFoundException
     * @throws \ReflectionException
     */
    public function get(\$id)
    {
        if (array_key_exists(\$id, \$this->cache))
            return \$this->cache[\$id];

        if (!array_key_exists(\$id, \$this->services)) {
            if (!class_exists(\$id))
                throw new ServiceNotFoundException('Unknown service "' . \$id . '"');

            \$reflection = new \ReflectionClass(\$id);
            \$arguments = [];
            if ((\$constructor = \$reflection->getConstructor()) !== null) {
                foreach (\$constructor->getParameters() as \$parameter) {
                    if (\$paramClass = \$parameter->getClass()) {
                        \$arguments[] = \$this->get(\$paramClass->getName());
                    } elseif (\$parameter->isArray()) {
                        \$arguments[] = [];
                    } else {
                        if (!\$parameter->isDefaultValueAvailable())
                            throw new ServiceNotFoundException('Unable to resolve "' . \$parameter->getName() . '" in service "' . \$id . '"');

                        \$arguments[] = \$parameter->getDefaultValue();
                    }
                }
            }
            \$this->cache[\$id] = \$reflection->newInstanceArgs(\$arguments);
            return \$this->cache[\$id];
        }

        if (\$this->services[\$id] instanceof \Closure) {
            \$this->cache[\$id] = \$this->services[\$id](\$this);
        } else {
            \$this->cache[\$id] = \$this->services[\$id];
        }

        return \$this->cache[\$id];
    }

    public function has(\$id)
    {
        return array_key_exists(\$id, \$this->services) || class_exists(\$id);
    }
}
PHP;

	$arTemplate['/install/index.php'] = <<<PHP
<?php defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use {{ module.namespace }}\Core\Installer;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

if (class_exists('{{ module.class }}')) {
    return;
}

include_once(dirname(__DIR__) . '/src/Core/Installer.php');

class {{ module.class }} extends Installer {
    public function __construct()
    {
        parent::__construct();

        \$this->MODULE_NAME = Loc::getMessage("CW_LL_MODULE_NAME");
        \$this->MODULE_DESCRIPTION = Loc::getMessage("CW_LL_MODULE_DESCRIPTION");
        \$this->PARTNER_NAME = Loc::getMessage("CW_LL_COMPANY_NAME");
        \$this->PARTNER_URI = Loc::getMessage("CW_LL_PARTNER_URI");
    }

    public function DoInstall()
    {
        parent::DoInstall();

        // Your code here, e.g.:
        // \$this->InstallDB();
        // \$this->InstallEvents();
    }

    public function DoUninstall()
    {
        // Your code here, e.g.:
        // \$this->UnInstallEvents();
        // \$this->UnInstallDB();

        parent::DoUninstall();
    }

}

// Need to be closed because of Bitrix obfuscation
?>
PHP;
	$arTemplate['/install/version.php'] = <<<PHP
<?php
\$arModuleVersion = array(
	"VERSION" => "{{ module.version }}",
	"VERSION_DATE" => "{{ module.versionDate }}"
);
PHP;

	$arTemplate['/lang/ru/options.php'] = <<<PHP
<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

\$MESS['{{ lang.prefix }}_MAIN'] = "Основные настройки";
\$MESS['{{ lang.prefix }}_ACTIVE'] = "Модуль активен";
\$MESS['{{ lang.prefix }}_UNINSTALL_SAVE_SETTINGS'] = "Сохранить настройки при удалении модуля";
\$MESS['{{ lang.prefix }}_SETTINGS_MODULE'] = "Основные настройки";

\$MESS['{{ lang.prefix }}_LOG_LEVEL'] = "Логировать";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_NONE'] = "Нет";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_ERROR'] = "Ошибки";
\$MESS['{{ lang.prefix }}_LOG_LEVEL_DEBUG'] = "Режим отладки";

PHP;
	$arTemplate['/lang/ru/install/index.php'] = <<<PHP
<?php
\$MESS["{{ lang.prefix }}_MODULE_NAME"] = "{{ module.name }}";
\$MESS["{{ lang.prefix }}_MODULE_DESCRIPTION"] = "{{ module.description}}";
\$MESS["{{ lang.prefix }}_COMPANY_NAME"] = "{{ vendor.name }}";
\$MESS["{{ lang.prefix }}_PARTNER_URI"] = "{{ vendor.site }}";
PHP;

	$arTemplate['/lib/controller/data.php'] = <<<PHP
<?php

namespace {{ module.namespace }}\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Connection extends Controller
{
    public function configureActions()
    {
        return [
            'some' => [
                'prefilters' => []
            ],
        ];
    }

    public function someAction()
    {

        return array('success' => true);
    }
}
PHP;

	$arTemplate['/vendor/Psr/Container/src/ContainerInterface.php'] = <<<PHP
<?php

namespace Psr\Container;

interface ContainerInterface {
	public function get(\$id);
	public function has(\$id);
}
PHP;
	$arTemplate['/vendor/Psr/Container/src/NotFoundExceptionInterface.php'] = <<<PHP
<?php

namespace Psr\Container;

interface NotFoundExceptionInterface {}
PHP;
	$arTemplate['/vendor/Psr/Log/src/LoggerInterface.php'] = <<<PHP
<?php

namespace Psr\Log;

/**
 * Describes a logger instance.
 *
 * The message MUST be a string or object implementing __toString().
 *
 * The message MAY contain placeholders in the form: {foo} where foo
 * will be replaced by the context data in key "foo".
 *
 * The context array can contain arbitrary data, the only assumption that
 * can be made by implementors is that if an Exception instance is given
 * to produce a stack trace, it MUST be in a key named "exception".
 *
 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 * for the full interface specification.
 */
interface LoggerInterface
{
    /**
     * System is unusable.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function emergency(\$message, array \$context = array());

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function alert(\$message, array \$context = array());

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function critical(\$message, array \$context = array());

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function error(\$message, array \$context = array());

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function warning(\$message, array \$context = array());

    /**
     * Normal but significant events.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function notice(\$message, array \$context = array());

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function info(\$message, array \$context = array());

    /**
     * Detailed debug information.
     *
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function debug(\$message, array \$context = array());

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed \$level
     * @param string \$message
     * @param array \$context
     * @return void
     */
    public function log(\$level, \$message, array \$context = array());
}
PHP;
	$arTemplate['/vendor/Psr/Log/src/LogLevel.php'] = <<<PHP
<?php

namespace Psr\Log;

/**
 * Describes log levels.
 */
class LogLevel
{
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const WARNING   = 'warning';
    const ALERT     = 'alert';
    const ERROR     = 'error';
    const CRITICAL  = 'critical';
    const EMERGENCY = 'emergency';
    const DEBUG     = 'debug';
}
PHP;

	$arTemplate['/.settings.php'] = <<<PHP
<?php
return [
    'controllers' => [
        'value' => [
            'namespaces' => [
                '\{{ module.namespace }}\Controller' => 'api',
            ],
        ],
        'readonly' => true,
    ],
];
PHP;
	$arTemplate['/config.php'] = <<<PHP
<?php

use {{ module.namespace }}\Module;
use {{ module.namespace }}\Core\Config\Options;
use {{ module.namespace }}\Core\Log;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

return [
	Options::class => function () {
		return new Options(Module::getModuleId());
	},
	LoggerInterface::class => function (ContainerInterface \$c) {
        \$logger = new Log\Logger();
        \$logHandler = new Log\FileLogHandler(\$c->get('log_file'));

        \$logger->setLogHandler(\$logHandler);

        /** @var Options \$options */
        \$options = \$c->get(Options::class);
        \$level = \$options->get('LOG_LEVEL', 0);
        \$logger->setLevel(\$level);

        return \$logger;
    },

    'log_file' => __DIR__ . '/logs/module.log',
];
PHP;
	$arTemplate['/default_option.php'] = <<<PHP
<?php

\${{ module.class }}_default_option = array(
	'ACTIVE' => 0,
	'UNINSTALL_SAVE_SETTINGS' => 0,
	'LOG_LEVEL' => 0,
);
PHP;
	$arTemplate['/include.php'] = <<<PHP
<?php

namespace {{ module.namespace }};

use {{ module.namespace }}\Core\BaseModule;

spl_autoload_register(function (\$class) {
	\$moduleNamespace = '{{ module.namespace | slashed }}\\\\';
	if (strpos(\$class, \$moduleNamespace) === 0) {
		\$path = __DIR__ . '/src/' . strtr(substr(\$class, strlen(\$moduleNamespace)), '\\\\', '/') . '.php';
		if (is_file(\$path))
			require_once \$path;
	} else {
	    list(\$vendor, \$library, \$file) = explode('\\\\', \$class, 3);
		\$path = __DIR__ . "/vendor/{\$vendor}/{\$library}/src/" . strtr(\$file, '\\\\', '/') . '.php';
		if (is_file(\$path))
			require_once \$path;
	}
});

final class Module extends BaseModule {

}

// Need to be closed because of Bitrix obfuscation
?>
PHP;
	$arTemplate['/options.php'] = <<<PHP
<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use {{ module.namespace }}\Module;

Loc::loadMessages(\$_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);

\$MODULE_ID = basename(__DIR__);
\$module_state = Loader::includeSharewareModule(\$MODULE_ID);
if (\$module_state === Loader::MODULE_DEMO_EXPIRED) {
	echo Loc::getMessage("MODULE_EXPIRED_DESCRIPTION_LINK");
	return;
}

ClearVars();

\$container = Module::getContainer();
\$options = Module::getOptions();
\$arOptions = \$options->all();


#
# Save
#
if ( \$_SERVER["REQUEST_METHOD"]=="POST" && (\$_REQUEST["save"] <> '' || \$_REQUEST["apply"] <> '') && check_bitrix_sessid()){
	\$options->save(\$_POST);
	LocalRedirect(\$APPLICATION->GetCurPageParam());
	exit;
}

#
# Tabs
#
\$aTabs = array();
\$aTabs[] = array("DIV" => "{{ lang.prefix }}_MAIN", "TAB" => GetMessage("{{ lang.prefix }}_MAIN"), "TITLE"=>GetMessage("{{ lang.prefix }}_MAIN"));
\$tabControl = new CAdminForm("user_edit", \$aTabs);

\$tabControl->Begin(array(
	"FORM_ACTION" => \$APPLICATION->GetCurPage()."?lang=".LANG."&mid=".Module::getModuleId()."&mid_menu=1",
	"FORM_ATTRIBUTES" => "",
));

\$tabControl->BeginEpilogContent();
echo bitrix_sessid_post();
\$tabControl->EndEpilogContent();

#
# Tab 1
#
\$tabControl->BeginNextFormTab();

\$tabControl->AddSection("{{ lang.prefix }}_SETTINGS_MODULE", GetMessage("{{ lang.prefix }}_SETTINGS_MODULE"));

\$tabControl->addCheckboxField('ACTIVE', GetMessage("{{ lang.prefix }}_ACTIVE"), false, 1, \$arOptions['ACTIVE']);
\$tabControl->addCheckboxField('UNINSTALL_SAVE_SETTINGS', GetMessage("{{ lang.prefix }}_UNINSTALL_SAVE_SETTINGS"), false, 1, \$arOptions['UNINSTALL_SAVE_SETTINGS']);
\$logLevels = [
	0 => GetMessage("{{ lang.prefix }}_LOG_LEVEL_NONE"),
	'error' => GetMessage("{{ lang.prefix }}_LOG_LEVEL_ERROR"),
	'debug' => GetMessage("{{ lang.prefix }}_LOG_LEVEL_DEBUG"),
];
\$tabControl->addDropdownField('LOG_LEVEL', GetMessage("{{ lang.prefix }}_LOG_LEVEL"), false, \$logLevels, \$arOptions['LOG_LEVEL']);


#
# Buttons
#
\$tabControl->Buttons(array(
	"disabled" => false,
	"btnSave" => true,
	"btnApply" => false,
	"btnCancel" => true,
	"btnSaveAndAdd" => false,
));


\$tabControl->Show();
PHP;



	#Asking user
	IO::Say("Module maker CLI v%s", '0.5');
	$inputs = [
		'module' => [
			'id' => 'local.lib',
			'version' => '1.0.0',
			'name' => 'Module example',
			'description' => 'Boilerplate for module',
		],
		'lang' => [
			'prefix' => 'CW_LL',
		],
		'vendor' => [
			'name' => 'Ctweb',
			'site' => 'https://ctweb.ru',
		]
	];

	$confirm = 'y';
	do {
		do {
			IO::Ask('Module ID (ex.: vendor.module.name)', $inputs['module']['id']);
		} while (!preg_match("/^[\w\d\.]+$/", $inputs['module']['id']) !== false);

		IO::Ask("Lang prefix", $inputs['lang']['prefix']);
		IO::Ask("Version", $inputs['module']['version']);

		IO::Ask("Name", $inputs['module']['name']);
		IO::Ask("Description", $inputs['module']['description']);
		IO::Ask("Vendor", $inputs['vendor']['name']);
		IO::Ask("Vendor site", $inputs['vendor']['site']);


		$inputs['module']['class'] = strtr($inputs['module']['id'], '.', '_');
		$inputs['module']['namespace'] = join('\\', array_map(function ($e) {
			return ucfirst($e);
		}, explode('.', $inputs['module']['id'])));

		$inputs['module']['versionDate'] = date_format(date_create(), 'Y-m-d H:i:s');


		#
		#   Confirm info
		#
		IO::Say("Installing module:");
		IO::Say("Module id: %s", $inputs['module']['id']);
		IO::Say("Module name: %s", $inputs['module']['name']);
		IO::Say("Module description: %s\n", $inputs['module']['description']);

		IO::Say("Module class: %s", $inputs['module']['class']);
		IO::Say("Module namespace: %s\n", $inputs['module']['namespace']);

		IO::Say("Version: %s", $inputs['module']['version']);
		IO::Say("Version date: %s\n", $inputs['module']['versionDate']);

		IO::Say("Vendor: %s", $inputs['vendor']['name']);
		IO::Say("Vendor site: %s\n", $inputs['vendor']['site']);

		IO::Say("Lang prefix: %s\n", $inputs['lang']['prefix']);

		IO::Ask("Is information correct? (Y|n)", $confirm);
	} while (trim(strtolower($confirm)) !== 'y');

	#Generate files
	$module_dir = __DIR__ . "/{$inputs['module']['id']}";

	$renderer = new Template($inputs);

	foreach ($arTemplate as $path => $template) {

		$compiledTemplate = $renderer->render($template);

		$path = (strpos($path, '/') === 0) ? $path : "/{$path}";
		Filesystem::CreateFile($module_dir . $path, $compiledTemplate);
	}
}
