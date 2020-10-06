<?php
namespace Vendor\ModuleName\Core\Log;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger {

    protected $levels_enabled = [];

    /** @var LogHandlerInterface */
    protected $handler;

    public function setLogHandler(LogHandlerInterface $handler)
    {
        $this->handler = $handler;
    }
    public function setLevelsEnabled(array $levels)
    {
        $this->levels_enabled = $levels;
    }

    public function log($level, $message, array $context = array())
    {
        if (\in_array($level, $this->levels_enabled) && $this->handler)
            $this->handler->log($level, $message, $context);
    }
}