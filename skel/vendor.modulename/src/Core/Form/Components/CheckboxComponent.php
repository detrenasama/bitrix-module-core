<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\LabeledComponent;
use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class CheckboxComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
?>
        <input type="checkbox" name="<?= \htmlspecialchars($this->id) ?>" value="1" <?= $this->value ? 'checked' : '' ?> />
<?php
    }
    public function save()
    {
        $options = Module::getOptions();
        $options->set($this->getId(), $_POST[$this->getId()] ? 1 : 0);
    }
}
