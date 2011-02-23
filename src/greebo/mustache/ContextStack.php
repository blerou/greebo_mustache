<?php
/**
 * This file is part of greebo mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * ContextStack class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class ContextStack
{
	/**
	 * @var array the stack itself
	 */
	private $stack = array();

	/**
	 * Constructor
	 *
	 * @param mixed $view    an array or an object of the view variables
	 * @param mixed $escaper a callable that escapes well
	 */
	public function __construct($view, $escaper = null)
	{
		$this->push($view);
		if (empty($escaper) || !is_callable($escaper)) {
			$escaper = function($value) {
					return htmlentities($value, ENT_COMPAT, 'UTF-8');
				};
		}
		$this->escaper = $escaper;
	}

	/**
	 * get the escaped value of the given variable
	 *
	 * @param string $name the name of the variable
	 *
	 * @return mixed
	 */
	public function get($name)
	{
		$value = $this->getRaw($name);

		if (is_scalar($value)) {
			return call_user_func($this->escaper, $value);
		}

		return $value;
	}

	/**
	 * get the raw value of the given variable
	 *
	 * @param string $name the name of the variable
	 *
	 * @return mixed
	 */
	public function getRaw($name)
	{
		foreach ($this->stack as $view) {
			if (is_array($view) && isset($view[$name])) {
				return $view[$name];
			} else if (is_object($view) && method_exists($view, $name)) {
				return $view->$name();
			} else if (is_object($view) && property_exists($view, $name)) {
				return $view->$name;
			} else if (is_object($view) && !$view instanceof \Closure && isset($view->$name)) {
				return $view->$name;
			}
		}
		// no variable found
		return null;
	}

	/**
	 * add new "context" to the stack
	 *
	 * @param mixed $view an array or an object of the view variables
	 *
	 * @return void
	 */
	public function push($view)
	{
		array_unshift($this->stack, $view);
	}

	/**
	 * remove "context" from the stack
	 *
	 * @return void
	 */
	public function pop()
	{
		return array_shift($this->stack);
	}

	/**
	 * determines a variable is iterable or not
	 *
	 * @param mixed $var a variable
	 *
	 * @return bool
	 */
	public function iterable($var)
	{
		if ($var instanceof \Traversable) {
			return true;
		}
		if (!is_array($var)) {
			return false;
		}
		$textKeys = array_filter(array_keys($var), 'is_string');

		return empty($textKeys);
	}

}

?>