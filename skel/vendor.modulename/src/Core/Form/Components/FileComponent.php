<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\LabeledComponent;
use Vendor\ModuleName\Core\Form\Saveable;
use CAdminFileDialog;
use Vendor\ModuleName\Core\Exceptions\NotImplementedException;

class FileComponent extends LabeledComponent implements Saveable
{
    protected function renderInput()
    {
        $id = \htmlspecialchars($this->getId());
        CAdminFileDialog::ShowScript(array(
            'event' => 'BX_FD_' . $this->getId(),
            'arResultDest' => array('FUNCTION_NAME' => 'BX_FD_ONRESULT_' . $this->getId()),
            'arPath' => array(),
            'select' => 'F',
            'operation' => 'O',
            'showUploadTab' => true,
            'showAddToMenuTab' => false,
            'fileFilter' => '',
            'allowAllFiles' => true,
            'SaveConfig' => true
        ));
?>
        <input type="text" name="<?= $id ?>" id="__FD_PARAM_<?= $id ?>" value="<?= \htmlspecialchars($this->value) ?>" />
        <input value="..." type="button" onclick="window.BX_FD_<?= $id ?>();" />
        <script>
            setTimeout(function() {
                if (BX("bx_fd_input_<?= \strtolower($id) ?>"))
                    BX("bx_fd_input_<?= \strtolower($id) ?>").onclick = window.BX_FD_<?= $id ?>;
            }, 200);
            window.BX_FD_ONRESULT_<?= $id ?> = function(filename, filepath) {
                var oInput = BX("__FD_PARAM_<?= $id ?>");
                if (typeof filename == "object")
                    oInput.value = filename.src;
                else
                    oInput.value = (filepath + "/" + filename).replace(/\/\//ig, '/');
            }
        </script>
<?php
    }
    
    public function save()
    {
        throw new NotImplementedException();
        // TODO: implement file save
    }
}
