<?php
namespace Vendor\ModuleName\Core\Form;

use InvalidArgumentException;
use Vendor\ModuleName\Core\Form\Components\TabComponent;

abstract class Component {
    protected $id;
    protected $value;
    protected $params = [];
    protected $tab;

	/**
	 * Component constructor.
	 * @param string $id
	 * @param array $params
	 * @param $value
	 * @param int $sort
	 */
    public function __construct($id, array $params, $value) {
        $this->id = $id;
        $this->params = $params;
        $this->value = $value ?: $params['default'];
        if (!strlen(trim($this->id)))
            throw new InvalidArgumentException("Empty {id} provided!");
    }
    public function getId() {
        return $this->id;
    }
    public function getTab() {
        return $this->tab;
    }
    public function setTab(TabComponent $tab) {
        $this->tab = $tab;
    }
    /**
     * Echo HTML
     *
     * @return void
     */
    abstract public function render();


    public function onAdd(FormBuilder $form) {
        // not required
    }
}