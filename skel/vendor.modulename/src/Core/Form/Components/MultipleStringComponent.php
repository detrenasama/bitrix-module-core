<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class MultipleStringComponent extends StringComponent implements Saveable
{
    public function render()
    {
        // TODO: wtf is that?
        foreach ($this->value as $val) {
            parent::render();
            $this->params['label'] = ' ';
        }
        parent::render();
    }
    protected function renderInput()
    {
?>
        <input type="text" name="<?= \htmlspecialchars($this->getId()) ?>[]" value="<?= \htmlspecialchars(current($this->value)) ?>" <? foreach ($this->params['attributes'] as $key => $value) {
                                                                                                                                            echo \htmlspecialchars($key), '="', \htmlspecialchars($value), '" ';
                                                                                                                                        } ?> />
<?
    }
    public function save()
    {
        $options = Module::getOptions();
        $options->set($this->getId(), $_POST[$this->getId()]);
    }
}
