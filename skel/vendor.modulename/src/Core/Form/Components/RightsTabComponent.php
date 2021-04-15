<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class RightsTabComponent extends TabComponent implements Saveable
{
    public function __construct()
    {
        parent::__construct('edit_access_tab', [
            'name' => 'Права доступа',
            'title' => 'Настройка прав доступа',
        ]);
    }
    public function render()
    {
        global $APPLICATION;
        $module_id = Module::getModuleId();
        parent::render();
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/group_rights.php");
    }
    public function save()
    {
        if (isset($_REQUEST['RIGHTS']) && isset($_REQUEST['GROUPS'])) {
            $options = Module::getOptions();
            \CMain::DelGroupRight(Module::getModuleId());
            $GROUP = $_REQUEST['GROUPS'];
            $RIGHT = $_REQUEST['RIGHTS'];
            foreach ($GROUP as $k => $v) {
                if ($k == 0) {
                    $options->set('GROUP_DEFAULT_RIGHT', $RIGHT[0], 'Right for groups by default');
                } else {
                    \CMain::SetGroupRight(Module::getModuleId(), $GROUP[$k], $RIGHT[$k]);
                }
            }
        }
    }
}
