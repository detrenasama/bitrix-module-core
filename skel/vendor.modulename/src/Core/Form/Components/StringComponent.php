<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\LabeledComponent;
use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class StringComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
?>
        <input type="text" name="<?= \htmlspecialchars($this->getId()) ?>" value="<?= \htmlspecialchars($this->value) ?>" <? foreach ($this->params['attributes'] as $key => $value) {
                                                                                                                                echo \htmlspecialchars($key), '="', \htmlspecialchars($value), '" ';
                                                                                                                            } ?> />
<?
    }
    public function save()
    {
        $options = Module::getOptions();
        $options->set($this->getId(), $_POST[(string) $this->getId()]);
    }
}
