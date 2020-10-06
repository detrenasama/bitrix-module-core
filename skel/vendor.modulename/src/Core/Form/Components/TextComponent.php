<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\LabeledComponent;
use Vendor\ModuleName\Core\Form\Saveable;
use Vendor\ModuleName\Module;

class TextComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
?>
        <textarea name="<?= $this->getId() ?>" <? foreach ($this->params['attributes'] as $key => $value) {
                                                    echo \htmlspecialchars($key), '="', \htmlspecialchars($value), '" ';
                                                } ?>><?= $this->value ?></textarea>
<?
    }
    
    public function save()
    {
        $options = Module::getOptions();
        $options->set($this->getId(), $_POST[$this->getId()]);
    }
}
