<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\LabeledComponent;

class ViewComponent extends LabeledComponent
{
	public function __construct($value, array $params = [])
	{
		parent::__construct($params['id'] ?: uniqid(), $params, $value);
	}

	protected function renderInput()
	{
		echo $this->value;
	}
}
