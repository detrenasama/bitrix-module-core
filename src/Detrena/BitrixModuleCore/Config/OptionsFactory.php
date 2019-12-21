<?php

namespace Detrena\BitrixModuleCore\Config;

class OptionsFactory {
    public function __invoke()
    {
        return new Options(\DetrenaTest\BitrixModuleCore\Module::getModuleId());
    }
}