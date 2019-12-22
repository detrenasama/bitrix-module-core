<?php

namespace Detrena\BitrixModuleCore\Core;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;

abstract class Installer extends \CModule
{
    public $MODULE_ID;
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS;

    public $PARTNER_NAME;
    public $PARTNER_URI;

    public function __construct()
    {
        $dir = $this->getModuleDir();
        $this->MODULE_ID = basename($dir);

        $arModuleVersion = array();
        include($dir . "/install/version.php");
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
    }

    public function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallFiles();
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        ModuleManager::unregisterModule($this->MODULE_ID);
    }

    public function InstallFiles()
    {
        // Components path mask: MODULE_PATH/install/components/MODULE_NAME.COMPONENT_NAME
        // Where MODULE_NAME in module '[vendor].[module]' is '[module]'

        $components = $this->GetModuleDir()."/install/components";
        if (Directory::isDirectoryExists($components)){
            CopyDirFiles($components, Application::getDocumentRoot() . "/bitrix/components/{$this->getVendor()}/", true, true);
        }

        // Directories will be copied in /bitrix/DIRECTORY_NAME/MODULE_ID/

        $files = glob($this->GetModuleDir()."/install/files/*/");
        foreach ($files as $dir) {
            $basename = basename($dir);
            CopyDirFiles($dir, Application::getDocumentRoot() . "/bitrix/{$basename}/{$this->MODULE_ID}/", true, true);
        }
    }

    public function UnInstallFiles()
    {
        $components = glob(Application::getDocumentRoot()."/bitrix/components/{$this->getVendor()}/{$this->getModuleCode()}.*/");
        foreach ($components as $dir)
            Directory::deleteDirectory($dir);

        $files = glob($this->GetModuleDir()."/install/files/*/");
        foreach ($files as $dir) {
            $basename = basename($dir);
            Directory::deleteDirectory(Application::getDocumentRoot()."/bitrix/{$basename}/{$this->MODULE_ID}");
        }
    }

    /**
     * @return string
     */
    private function getModuleDir()
    {
        return dirname(dirname(__DIR__));
    }

    /**
     * @return string
     */
    private function getVendor()
    {
        return (string) substr($this->MODULE_ID, 0, strpos($this->MODULE_ID, '.'));
    }

    /**
     * @return string
     */
    private function getModuleCode()
    {
        return (string) substr($this->MODULE_ID, strpos($this->MODULE_ID, '.')+1);
    }
}