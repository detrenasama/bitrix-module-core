<?php

use Psr\Log\LogLevel;
use Vendor\ModuleName\Module;
use Vendor\ModuleName\Core\Form\Components;
use Vendor\ModuleName\Core\Form\FormBuilder;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"] . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);
$module_id = basename(__DIR__);
$module_state = Loader::includeSharewareModule($module_id);
if ($module_state === Loader::MODULE_DEMO_EXPIRED) {
    echo Loc::getMessage("MODULE_EXPIRED_DESCRIPTION_LINK");
    return;
}
ClearVars();

$container = Module::getContainer();
$options = Module::getOptions();
$arOptions = $options->all();

$form = new FormBuilder();

$form->add(new Components\TabComponent('main_tab', [
    'name' => Loc::getMessage("V_MN_SETTINGS_MODULE")
]));

$form->add(new Components\HeaderComponent(Loc::getMessage("V_MN_MAIN")));

$form->add(new Components\CheckboxComponent('ACTIVE', [
    'label' => Loc::getMessage("V_MN_ACTIVE"),
], $arOptions['ACTIVE']));

$form->add(new Components\CheckboxComponent('UNINSTALL_SAVE_SETTINGS', [
    'label' => Loc::getMessage("V_MN_UNINSTALL_SAVE_SETTINGS"),
], $arOptions['UNINSTALL_SAVE_SETTINGS']));

$form->add(new Components\MultipleSelectComponent('LOG_LEVELS', [
    'label' => Loc::getMessage("V_MN_LOG_LEVELS"),
    'values' => [
        LogLevel::NOTICE => Loc::getMessage("V_MN_LOG_LEVEL_NOTICE"),
        LogLevel::INFO => Loc::getMessage("V_MN_LOG_LEVEL_INFO"),
        LogLevel::WARNING => Loc::getMessage("V_MN_LOG_LEVEL_WARNING"),
        LogLevel::ALERT => Loc::getMessage("V_MN_LOG_LEVEL_ALERT"),
        LogLevel::ERROR => Loc::getMessage("V_MN_LOG_LEVEL_ERROR"),
        LogLevel::CRITICAL => Loc::getMessage("V_MN_LOG_LEVEL_CRITICAL"),
        LogLevel::EMERGENCY => Loc::getMessage("V_MN_LOG_LEVEL_EMERGENCY"),
        LogLevel::DEBUG => Loc::getMessage("V_MN_LOG_LEVEL_DEBUG"),
    ]
], $arOptions['LOG_LEVELS']));


$form->add(new Components\RightsTabComponent);

// Save form
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($_REQUEST["save"] <> '') && check_bitrix_sessid()) {
    $form->save();

    LocalRedirect($APPLICATION->GetCurPageParam());
    exit;
}

// Render form
$form->render();