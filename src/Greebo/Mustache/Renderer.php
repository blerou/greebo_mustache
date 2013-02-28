<?php
/**
 * This file is part of Greebo Mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace Greebo\Mustache;

/**
 * Renderer interface
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
interface Renderer
{
	/**
	 * set up partial definition
	 *
	 * @param array $patrials
	 *
	 * @return Renderer
	 */
	public function withPartials(array $patrials = null);

	/**
	 * renders the given template
	 *
	 * @param string       $template
	 * @param ContextStack $context
	 *
	 * @return string
	 */
	public function renderTemplate($template, ContextStack $context);

	/**
	 * renders the given partial
	 *
	 * @param string       $partial
	 * @param ContextStack $context
	 *
	 * @return string
	 */
	public function renderPartial($partial, ContextStack $context);
}

?>