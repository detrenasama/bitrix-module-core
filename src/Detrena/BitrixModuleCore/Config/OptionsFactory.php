<?php

namespace Detrena\BitrixModuleCore\Config;

use DetrenaTest\BitrixModuleCore\Module;

class OptionsFactory {
    public function __invoke()
    {
        return new Options(Module::getModuleId());
    }
}