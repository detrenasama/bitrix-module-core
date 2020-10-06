<?php
namespace Vendor\ModuleName\Core\Form;

/**
 * If form component saving data on POST, implement this
 * @package Vendor\ModuleName\Core\Form
 */
interface Saveable {
    public function save();
}