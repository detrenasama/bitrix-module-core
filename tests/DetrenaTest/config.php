<?php

use Detrena\BitrixModuleCore\Config\Options;
use Detrena\BitrixModuleCore\Config\OptionsFactory;
use Detrena\BitrixModuleCore\Log\LoggerFactory;
use Psr\Log\LoggerInterface;

return [
    'dependencies' => [
        'factories' => [
            Options::class => OptionsFactory::class,
            LoggerInterface::class => LoggerFactory::class,
        ]
    ],
    'log_file' => __DIR__ . '/logs/log',
    'cache_config' => false,
];