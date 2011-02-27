<?php
/**
 * This file is part of greebo mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * Mustache class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class Mustache
{
	private $generator;

	public function __construct(Generator $generator = null)
	{
		if (empty($generator)) {
			$this->generator = new JitGenerator(
				new Tokenizer(),
				new TemplateLoader()
			);
		} else {
			$this->generator = $generator;
		}
	}

	public function render($template, $view = null, $partials = null)
	{
		$generated = $this->generator->compile($template, $partials);
		$context   = new ContextStack($view);
		$compile   = function($generated, $context) {
			eval($generated);
			return $result;
		};

		return $compile($generated, $context);
	}
}

?>