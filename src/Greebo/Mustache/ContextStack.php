<?php
/**
 * This file is part of Greebo Mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace Greebo\Mustache;

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
	 * @var callable the escaper function
	 */
	private $escaper;

	/**
	 * Constructor
	 *
	 * @param callable $escaper
	 */
	public function __construct($escaper = null)
	{
		if (!is_callable($escaper)) {
			$escaper = function($value) {
				return htmlentities($value, ENT_COMPAT, 'UTF-8');
			};
		}
		$this->escaper = $escaper;
	}

	/**
	 * get the escaped value of the given variable
	 *
	 * @param string $variableName
	 *
	 * @return mixed
	 */
	public function get($variableName)
	{
		$value = $this->getRaw($variableName);

		if (is_scalar($value)) {
			return call_user_func($this->escaper, $value);
		}

		return $value;
	}

	/**
	 * get the raw value of the given variable
	 *
	 * @param string $variableName
	 *
	 * @return mixed
	 */
	public function getRaw($variableName)
	{
		foreach ($this->stack as $view) {
			if (is_array($view) && isset($view[$variableName])) {
				return $view[$variableName];
			} else if (is_object($view) && method_exists($view, $variableName)) {
				return $view->$variableName();
			} else if (is_object($view) && property_exists($view, $variableName)) {
				return $view->$variableName;
			} else if (is_object($view) && !$view instanceof \Closure && isset($view->$variableName)) {
				return $view->$variableName;
			}
		}
		// no variable found
		return null;
	}

	/**
	 * add new "context" to the stack
	 *
	 * @param mixed $view
	 *
	 * @return void
	 */
	public function push($view)
	{
		array_unshift($this->stack, $view);
	}

	/**
	 * remove "context"
	 *
	 * @return mixed
	 */
	public function pop()
	{
		return array_shift($this->stack);
	}

	/**
	 * determines a variable is iterable or not
	 *
	 * @param mixed $variable
	 *
	 * @return bool
	 */
	public function iterable($variable)
	{
		if ($variable instanceof \Traversable) {
			return true;
		}
		if (!is_array($variable)) {
			return false;
		}
		$textKeys = array_filter(array_keys($variable), 'is_string');

		return empty($textKeys);
	}
}

?>