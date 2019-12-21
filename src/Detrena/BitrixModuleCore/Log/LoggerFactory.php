<?php

namespace Detrena\BitrixModuleCore\Log;

use Detrena\BitrixModuleCore\Config\Options;
use Psr\Container\ContainerInterface;

class LoggerFactory {
    public function __invoke(ContainerInterface $c)
    {
        $logger = new Logger();
        $logHandler = new FileLogHandler($c->get('log_file'));

        $logger->setLogHandler($logHandler);

        /** @var Options $options */
        $options = $c->get(Options::class);
        $level = $options->get('LOG_LEVEL', 0);
        $logger->setLevel($level);

        return $logger;
    }
}