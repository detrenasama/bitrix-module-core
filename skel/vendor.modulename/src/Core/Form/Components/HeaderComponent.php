<?php

namespace Vendor\ModuleName\Core\Form\Components;

use Vendor\ModuleName\Core\Form\Component;

class HeaderComponent extends Component
{

    /**
     * HeaderComponent constructor.
     * @param string $title
     */
    public function __construct($title)
    {
        parent::__construct(\uniqid('header_'), [], $title);
    }

    public function render()
    {
        echo '<tr class="heading"><td colspan="2">', \htmlspecialchars($this->value), '</td></tr>';
    }
}
