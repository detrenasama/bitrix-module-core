<?php

declare(strict_types=1);

namespace Bitrix\Main\Config;

class Option {
    public static function getDefaults(string $moduleId) : array
    {
        return [];
    }

    public static function getForModule(string $moduleId) : array
    {
        return [];
    }

    public static function get(string $moduleId, string $name, string $default = null, string $siteId = null) : ?string
    {
        return '';
    }

    public static function set(string $moduleId, string $name, string $value, string $siteId = null) : void
    {

    }

    public static function delete(string $module, array $filter)
    {

    }
}