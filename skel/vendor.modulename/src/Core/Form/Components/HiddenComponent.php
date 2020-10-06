<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\Component;
use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class HiddenComponent extends Component implements Saveable
{
    public function render()
    {
?>
        <input type="hidden" name="<?= $this->getId() ?>" value="<?= \htmlspecialchars($this->value) ?>" />
<?php
    }
    
    public function save()
    {
        $options = Module::getOptions();
        $options->set($this->getId(), $_POST[$this->getId()]);
    }
}
