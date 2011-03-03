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
			$generator = new JitGenerator(
				new Tokenizer(),
				new TemplateLoader()
			);
		}

		$this->generator = $generator;
	}

	public function render($template, $view = null, $partials = null)
	{
		$compiler = function($generated, $context) {
			eval($generated);
			return $result;
		};

		$context = new ContextStack($this->generator, $compiler, $partials);
		$context->push($view);

		return $context->renderTemplate($template);
	}
}

?>