<?php

namespace Detrena\BitrixModuleCore\Log;

class FileLogHandler implements LogHandlerInterface {
    protected $file;

    /** @var resource */
    protected $stream;
    protected $template = "{time} [{level}]: {message}
";

    public function __construct($file)
    {
        $this->file = (string) $file;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @throws \Exception
     */
    public function log($level, $message, array $context = array())
    {
        if (!$this->stream) {
            if (!file_exists(dirname($this->file)))
                mkdir(dirname($this->file), 0755, true);

            $this->stream = fopen($this->file, "a+");
            if (!$this->stream)
                throw new \Exception(sprintf("Cannot write into '%s'!", $this->file));
        }

        if ($this->stream)
            $this->write($level, $message, $context);
    }

    protected function write($level, $message, array $context = array())
    {
        fwrite($this->stream, $this->interpolate($this->template, array(
            'time' => date("Y-m-d H:i:s"),
            'level' => $level,
            'message' => $this->interpolate($message, $context),
        )));
    }

    protected function interpolate($message, array $context = array())
    {
        $replace = array();
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            } else {
                $replace['{' . $key . '}'] = var_export($val,true);
            }
        }

        return strtr($message, $replace);
    }

    public function __destruct()
    {
        if ($this->stream)
            fclose($this->stream);
    }
}