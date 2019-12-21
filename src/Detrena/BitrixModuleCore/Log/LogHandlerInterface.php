<?php

namespace Detrena\BitrixModuleCore\Log;

interface LogHandlerInterface {
    public function log($level, $message, array $context = array());
}