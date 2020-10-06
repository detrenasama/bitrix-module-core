<?php

namespace Vendor\ModuleName\Core\Log;

use Vendor\ModuleName\Core\Config\Options;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;

class LoggerFactory {
    public function __invoke(ContainerInterface $c)
    {
        /** @var Options $options */
        $options = $c->get(Options::class);
        $levels = $options->get('LOG_LEVELS', []);

        if (empty($levels))
            return new NullLogger;

        $logger = new Logger();
        $logHandler = new FileLogHandler($c->get('log_file'));

        $logger->setLogHandler($logHandler);
        $logger->setLevelsEnabled($levels);

        return $logger;
    }
}