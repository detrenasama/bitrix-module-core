<?php

namespace Detrena\BitrixModuleCore\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class Logger implements LoggerInterface {
    protected $levelMap = array(
        LogLevel::NOTICE => 1,
        LogLevel::INFO => 2,
        LogLevel::WARNING => 3,
        LogLevel::ALERT => 4,
        LogLevel::ERROR => 5,
        LogLevel::CRITICAL => 6,
        LogLevel::EMERGENCY => 7,
        LogLevel::DEBUG => 8,
    );

    protected $level;
    /** @var LogHandlerInterface */
    protected $handler;

    public function setLogHandler(LogHandlerInterface $handler)
    {
        $this->handler = $handler;
    }

    public function setLevel($level)
    {
        $this->level = $level;
    }

    public function emergency($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::EMERGENCY])
            $this->log(LogLevel::EMERGENCY, $message, $context);
    }
    public function alert($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::ALERT])
            $this->log(LogLevel::ALERT, $message, $context);
    }
    public function critical($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::CRITICAL])
            $this->log(LogLevel::CRITICAL, $message, $context);
    }
    public function error($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::ERROR])
            $this->log(LogLevel::ERROR, $message, $context);
    }
    public function warning($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::WARNING])
            $this->log(LogLevel::WARNING, $message, $context);
    }
    public function notice($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::NOTICE])
            $this->log(LogLevel::NOTICE, $message, $context);
    }
    public function info($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::INFO])
            $this->log(LogLevel::INFO, $message, $context);
    }
    public function debug($message, array $context = array())
    {
        if ($this->level && $this->levelMap[$this->level] >= $this->levelMap[LogLevel::DEBUG])
            $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        if ($this->handler)
            $this->handler->log($level, $message, $context);
    }
}