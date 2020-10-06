<?php
spl_autoload_register(function ($class) {
	$moduleNamespace = 'Vendor\\ModuleName\\';
	if (strpos($class, $moduleNamespace) === 0) {
		$path = dirname(__DIR__) . '/src/' . strtr(substr($class, strlen($moduleNamespace)), '\\', '/') . '.php';
		if (is_file($path))
			require_once $path;
		$functionsPath = dirname(__DIR__) . "/src/functions.php";
		if (is_file($functionsPath))
			require_once($functionsPath);
	} else {
	    list($vendor, $library, $file) = explode('\\', $class, 3);
		$path = __DIR__ . "/{$vendor}/{$library}/src/" . strtr($file, '\\', '/') . '.php';
		if (is_file($path))
			require_once $path;
		$functionsPath = __DIR__ . "/{$vendor}/{$library}/src/functions.php";
		if (is_file($functionsPath))
			require_once($functionsPath);
	}
});