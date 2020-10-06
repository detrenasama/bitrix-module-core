<?php

use Vendor\ModuleName\Core\Config\Options;
use Vendor\ModuleName\Core\Config\OptionsFactory;
use Vendor\ModuleName\Core\Log\LoggerFactory;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'factories' => [
            Options::class => OptionsFactory::class,
            LoggerInterface::class => LoggerFactory::class,
        ]
    ],
    'log_file' => __DIR__ . '/logs/module-log.txt',
];