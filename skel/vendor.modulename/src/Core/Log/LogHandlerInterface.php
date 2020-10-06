<?php

namespace Vendor\ModuleName\Core\Log;

interface LogHandlerInterface {
    public function log($level, $message, array $context = array());
}