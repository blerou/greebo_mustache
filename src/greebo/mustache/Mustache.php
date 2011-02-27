<?php
/**
 * This file is part of greebo mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

class Mustache
{
	public function __construct(array $templatePaths = array(), $suffix = '.mustache')
	{
		$this->generator = new JitGenerator(
			new Tokenizer(),
			new TemplateLoader($templatePaths, $suffix)
		);
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