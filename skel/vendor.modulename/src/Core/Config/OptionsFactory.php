<?php

namespace Vendor\ModuleName\Core\Config;

use Vendor\ModuleName\Module;

class OptionsFactory {
    public function __invoke()
    {
        return new Options(Module::getModuleId());
    }
}