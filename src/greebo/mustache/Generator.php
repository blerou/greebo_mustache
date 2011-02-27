<?php
/**
 * This file is part of greebo mustache
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * Generator.php interface
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
interface Generator
{
	/**
	 * compiles the given template and use the given partials' definition
	 *
	 * @param string $template the template
	 * @param array  $partials the partials' definition
	 *
	 * @return string
	 */
	public function compile($template, $partials);
}

?>