<?php

namespace Vendor\ModuleName\Core\Form;

use Vendor\ModuleName\Core\Config\Form\Components;
use Vendor\ModuleName\Module;
use InvalidArgumentException;

class FormBuilder implements Saveable
{
    /** @var Component[] */
    protected $components = [];
    /** @var TabComponent[] */
    protected $tabs = [];

    public function add(Component $component)
    {
        if ($component instanceof Components\TabComponent)
            $this->tabs[] = $component;
        else {
            if (empty($this->tabs))
                throw new InvalidArgumentException("Should be at least 1 tab before adding other components");
            $component->setTab(end($this->tabs));
            $this->components[] = $component;
        }

        $component->onAdd($this);
    }
    public function save()
    {
        foreach ($this->tabs as $tab) {
            if ($tab instanceof Saveable) {
                $tab->save();
            }
        }
        foreach ($this->components as $component) {
            if ($component instanceof Saveable) {
                $component->save();
            }
        }
    }
    /**
     * @return void
     */
    public function render()
    {
        global $APPLICATION;
        $arTabs = [];
        foreach ($this->tabs as $tab) {
            $arTabs[] = $tab->getArray();
        }
        $tabControl = new \CAdminTabControl('tabControl', $arTabs);
        $tabControl->Begin();
        echo '<form name="' . Module::getModuleId() . '" method="POST" action="' . $APPLICATION->GetCurPage() . '?mid=' . Module::getModuleId() . '&lang=' . LANGUAGE_ID . '" enctype="multipart/form-data">' . bitrix_sessid_post();
        foreach ($this->tabs as $tab) {
            $tabControl->BeginNextTab();
            $tab->render();
            $tabComponents = \array_filter($this->components, function ($e) use ($tab) {
                return $e->getTab() === $tab;
            });
            foreach ($tabComponents as $component) {
                $component->render();
            }
        }
        $tabControl->Buttons();
        echo     '<input type="submit" name="save" value="Сохранить" />
					<input type="reset" name="reset" value="Отменить" />
                    </form>';
        $tabControl->End();
    }
}
