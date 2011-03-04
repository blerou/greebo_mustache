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
	 * generates php template
	 *
	 * @param string $template the template
	 *
	 * @return string
	 */
	public function generate($template);
}

?>