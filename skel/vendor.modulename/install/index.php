<?php defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();

use Vendor\ModuleName\Core\Installer;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

if (class_exists('vendor_modulename')) {
    return;
}

require_once(dirname(__DIR__) . '/src/Core/Installer.php');

class vendor_modulename extends CModule {
    use Installer {
        Installer::DoInstall as install;
        Installer::DoUninstall as uninstall;
    }

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

        $this->MODULE_NAME = Loc::getMessage("V_MN_MODULE_NAME");
        $this->MODULE_DESCRIPTION = Loc::getMessage("V_MN_MODULE_DESCRIPTION");
        $this->PARTNER_NAME = Loc::getMessage("V_MN_COMPANY_NAME");
        $this->PARTNER_URI = Loc::getMessage("V_MN_PARTNER_URI");
    }

    public function DoInstall()
    {
        $this->install();

        // Your code here, e.g.:
        $this->InstallDB();
        // $this->InstallEvents();
    }

    public function DoUninstall()
    {
        // Your code here, e.g.:
        // $this->UnInstallEvents();
        $this->UnInstallDB();

        $this->uninstall();
    }

    public function InstallDB()
	{
        global $APPLICATION, $DB;
        $dbBatchFile = __DIR__.'/batch/db/'.strtolower($DB->type).'/install.sql';

        if (is_file($dbBatchFile))
		    $this->errors = $DB->RunSQLBatch($dbBatchFile);

		if (is_array($this->errors))
		{
			$APPLICATION->ThrowException(implode(' ', $this->errors));
			return false;
		}

		return true;
	}

	public function uninstallDB()
	{
		global $APPLICATION, $DB;

		if (!Option::get($this->MODULE_ID, 'UNINSTALL_SAVE_SETTINGS', 1)) {
            $dbBatchFile = __DIR__.'/batch/db/'.strtolower($DB->type).'/uninstall.sql';
            if (is_file($dbBatchFile))
			    $this->errors = $DB->RunSQLBatch($dbBatchFile);
		}

		if (is_array($this->errors))
		{
			$APPLICATION->ThrowException(implode(' ', $this->errors));
			return false;
		}

		return true;
	}

}

// Need to be closed because of Bitrix obfuscation
