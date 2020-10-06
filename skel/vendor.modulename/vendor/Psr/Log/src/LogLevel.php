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