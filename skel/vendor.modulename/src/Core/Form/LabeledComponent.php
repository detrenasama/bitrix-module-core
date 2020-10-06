<?php
namespace Vendor\ModuleName\Core\Form;

abstract class LabeledComponent extends Component {
    public function render() {
        if (isset($this->params['label'])) {
            echo '<tr id="tr_'.$this->getId().'"><td valign="top" width="40%">', $this->params['required'] ? '<b>' : '', $this->params['label'], $this->params['required'] ? '</b>' : '', '</td><td valign="top" nowrap>';
        } else {
            echo '<tr id="tr_'.$this->getId().'"><td valign="top" colspan="2" align="center">';
        }
        $this->renderInput();
        echo '</td></tr>';
    }
    abstract protected function renderInput();
}