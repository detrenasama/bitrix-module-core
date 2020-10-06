<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\Component;
use \CAdminTabControl;

class TabComponent extends Component
{
    protected $control;

    /**
     * TabComponent constructor.
     * @param string $id
     * @param array $params
     * @param int $sort
     */
    public function __construct($id, array $params)
    {
        parent::__construct($id, $params, null);
    }

    public function setControl(CAdminTabControl $control)
    {
        $this->control = $control;
    }

    /**
     * 
     * @return array 
     */
    public function getArray()
    {
        return array(
            'DIV' => $this->getId(),
            'TAB' => $this->params['name'],
            'ICON' => '',
            'TITLE' => $this->params['title'] ?: $this->params['name']
        );
    }

    /**
     * Echo html
     * @return void 
     */
    public function render()
    {
        // empty
    }
}
