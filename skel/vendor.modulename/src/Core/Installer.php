<?php

namespace Vendor\ModuleName\Core;

use Bitrix\Main\Application;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;

trait Installer 
{
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
            \CopyDirFiles($components, Application::getDocumentRoot() . "/bitrix/components/{$this->getVendor()}/", true, true);
        }

        // Directories will be copied into /bitrix/DIRECTORY_NAME/MODULE_ID/

        $files = \glob($this->GetModuleDir()."/install/files/*/");
        foreach ($files as $dir) {
            $basename = \basename($dir);
            \CopyDirFiles($dir, Application::getDocumentRoot() . "/bitrix/{$basename}/{$this->MODULE_ID}/", true, true);
        }

        // Files will be copied into /bitrix/admin/

        $files = \glob($this->GetModuleDir()."/install/admin/*.php");
        foreach ($files as $file) {
            $basename = $this->MODULE_ID . '_' . \basename($file);
            \CopyDirFiles($file, Application::getDocumentRoot() . "/bitrix/admin/{$basename}", true, true);
        }
    }

    public function UnInstallFiles()
    {
        $components = \glob(Application::getDocumentRoot()."/bitrix/components/{$this->getVendor()}/{$this->getModuleCode()}.*/");
        foreach ($components as $dir)
            Directory::deleteDirectory($dir);

        $files = \glob($this->GetModuleDir()."/install/files/*/");
        foreach ($files as $dir) {
            $basename = \basename($dir);
            Directory::deleteDirectory(Application::getDocumentRoot()."/bitrix/{$basename}/{$this->MODULE_ID}");
        }

        $files = \glob($this->GetModuleDir()."/install/admin/*.php");
        foreach ($files as $file) {
            $basename = $this->MODULE_ID . '_' . \basename($file);
            \unlink(Application::getDocumentRoot()."/bitrix/admin/{$basename}");
        }
    }

    /**
     * @return string
     */
    private function getModuleDir()
    {
        return \dirname(__DIR__, 2);
    }

    /**
     * @return string
     */
    private function getVendor()
    {
        return (string) \substr($this->MODULE_ID, 0, \strpos($this->MODULE_ID, '.'));
    }

    /**
     * @return string
     */
    private function getModuleCode()
    {
        return (string) \substr($this->MODULE_ID, \strpos($this->MODULE_ID, '.')+1);
    }
}