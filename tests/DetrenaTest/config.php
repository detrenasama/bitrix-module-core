<?php

return [
    \Detrena\BitrixModuleCore\Config\Options::class => \Detrena\BitrixModuleCore\Config\OptionsFactory::class,
    \Psr\Log\LoggerInterface::class => \Detrena\BitrixModuleCore\Log\LoggerFactory::class,
    'log_file' => dirname(__DIR__) . '/logs/log',
    'cache_config' => false,
];