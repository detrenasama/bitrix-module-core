<?php

namespace Vendor\ModuleName\Core\Config;

use Bitrix\Main\Config\Option;

class Options {
    protected $moduleId;

    public function __construct($moduleId)
    {
        $this->moduleId = $moduleId;
    }

    /**
     * @return array
     */
	public function defaults()
	{
		return Option::getDefaults($this->moduleId);
	}

    /**
     * @return array
     */
	public function real()
	{
		return Option::getForModule($this->moduleId);
	}
    public function get($name, $default = null, $siteId = false)
    {
        return $this->dbValue(Option::get($this->moduleId, $name, $default, $siteId));
    }
    public function set($name, $value = '', $siteId = '')
    {
        Option::set($this->moduleId, $name, $this->safeValue($value), $siteId);
    }
	public function delete(array $filter = [])
	{
		Option::delete($this->moduleId, $filter);
	}


	public function all()
	{
		$defaults = $this->defaults();
		$real = $this->real();
		$options = array_merge($defaults, $real);
		return array_map([$this, 'dbValue'], $options);
	}

	public function save($arOptions)
	{
		$options = $this->defaults();
		foreach ($options as $key => $value) {
			if (isset($arOptions[$key]))
				$this->set($key, $arOptions[$key]);
			else
				$this->set($key, $value);
		}
    }

	protected function safeKey($key)
	{
		if (strlen($key) > 40)
			throw new \InvalidArgumentException("{key} length must be less or equal 40");

		return preg_replace('/[^\d\w\_]/' , '_', $key);
    }

	protected function safeValue($value)
	{
		if (is_object($value))
			throw new \InvalidArgumentException("{value} cannot be an object!");

		if (is_array($value))
			return serialize($value);

		return $value;
    }

	protected function dbValue($value)
	{
		if (is_string($value) && is_array($res = unserialize($value)))
			return $res;

		return $value;
    }
}