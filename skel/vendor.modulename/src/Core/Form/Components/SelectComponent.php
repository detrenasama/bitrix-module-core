<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\LabeledComponent;
use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class SelectComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
?>
        <select name="<?= \htmlspecialchars($this->getId()) ?>" <? foreach ($this->params['attributes'] as $key => $value) {
                                                                    echo \htmlspecialchars($key), '="', \htmlspecialchars($value), '" ';
                                                                } ?> <?= $this->params['required'] ? 'required' : '' ?>>
            <? if (!$this->params['required']) : ?>
                <option value=""><?= $this->params['no_value_text'] ?></option>
            <? endif ?>
            <? foreach ($this->params['values'] as $value => $name) : ?>
                <option value="<?= \htmlspecialchars($value) ?>" <?= $value == $this->value ? 'selected' : '' ?>><?= \htmlspecialchars($name) ?></option>
            <? endforeach ?>
        </select>
<?
    }
    public function save()
    {
        $options = Module::getOptions();
        $options->set($this->getId(), $_POST[$this->getId()]);
    }
}
