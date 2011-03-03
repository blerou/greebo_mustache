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
	 * @var Generator the generator object
	 */
	private $generator;

	/**
	 * @var array the stack itself
	 */
	private $stack = array();

	/**
	 * @var \Closure the escaper function
	 */
	private $escaper;

	/**
	 * Constructor
	 *
	 * @param Generator $generator the generator object
	 */
	public function __construct(Generator $generator, $compliler, $patrials)
	{
		$this->generator = $generator;
		$this->compiler  = $compliler;
		$this->partials  = $patrials;
		$this->escaper   = function($value) {
			return htmlentities($value, ENT_COMPAT, 'UTF-8');
		};
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

	/**
	 * renders the given template
	 *
	 * @param string $template the template name
	 * 
	 * @return string
	 */
	public function renderTemplate($template)
	{
		$generated = $this->generator->compile($template, $this->partials);
		$compiler  = $this->compiler;

		return $compiler($generated, $this);
	}
}

?>