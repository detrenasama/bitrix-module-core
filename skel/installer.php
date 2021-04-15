<?php

namespace IO {
    class IO
    {
        static function Ask($message, &$var)
        {
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

        static function Say($message, $a1 = null, $a2 = null, $a3 = null, $a4 = null, $a5 = null)
        {
            printf("$message\n", $a1, $a2, $a3, $a4, $a5);
        }
    }

    class Filesystem
    {
        static function CreateFile($path, $content)
        {
            if (!file_exists(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, $content);
        }
    }

    class Progress
    {
        static $progress = null;

        static function ProgressStart($message, $value = '0%')
        {
            static::$progress = $message;
            self::ProgressUpdate($value);
        }
        static function ProgressEnd()
        {
            if (static::$progress) {
                static::$progress = null;
                fwrite(STDOUT, "\n");
            }
        }
        static function ProgressUpdate($value)
        {
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

    class Template
    {
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
        private function arrayValue($var, $param)
        {
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

    $arTemplate = {{templates}};

    #Asking user
    IO::Say("Module maker CLI v%s", '0.7');
    $inputs = [
        'module' => [
            'id' => 'my.module',
            'version' => '1.0.0',
            'name' => 'Module example',
            'description' => 'Boilerplate for module',
        ],
        'lang' => [
            'prefix' => 'MY_MD',
        ],
        'vendor' => [
            'name' => 'Me',
            'site' => 'https://localhost.ru',
        ]
    ];

    $confirm = 'y';
    do {
        do {
            IO::Ask('Module namespace (ex.: Vendor\\ModuleName)', $inputs['module']['namespace']);
        } while (!preg_match("/^[A-Z][\w\d]+\\\\[A-Z][\w\d]+$/", $inputs['module']['namespace']) !== false);

        $inputs['lang']['prefix'] = join('_', array_map(function ($e) {
            $expr = '/[A-Z]/';
            preg_match_all($expr, $e, $matches);
            $result = implode('', $matches[0]);
            return $result;
        }, explode('\\', $inputs['module']['namespace'])));

        IO::Ask("Lang prefix", $inputs['lang']['prefix']);
        IO::Ask("Version", $inputs['module']['version']);

        IO::Ask("Name", $inputs['module']['name']);
        IO::Ask("Description", $inputs['module']['description']);

        $inputs['vendor']['name'] = explode('\\', $inputs['module']['namespace'])[0];
        $inputs['vendor']['site'] = 'https://' . strtolower($inputs['vendor']['name']) . '.ru';

        IO::Ask("Vendor", $inputs['vendor']['name']);
        IO::Ask("Vendor site", $inputs['vendor']['site']);

        $inputs['module']['id'] = strtolower(str_replace('\\', '.', $inputs['module']['namespace']));
        $inputs['module']['class'] = strtr($inputs['module']['id'], '.', '_');

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

        $compiledTemplate = $renderer->render(base64_decode($template));

        $path = (strpos($path, '/') === 0) ? $path : "/{$path}";
        Filesystem::CreateFile($module_dir . $path, $compiledTemplate);
    }
}
