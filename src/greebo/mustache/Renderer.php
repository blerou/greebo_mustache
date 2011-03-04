<?php
/**
 * This file is part of greebo mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * Renderer class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class Renderer
{
	/**
	 * @var Generator the generator object
	 */
	private $generator;

	/**
	 * @var TemplateLoader the template loader object
	 */
	private $templateLoader;

	/**
	 * @var array the partial definition map
	 */
	private $partials;

	/**
	 * @var \Closure the compiler function
	 */
	private $compiler;

	/**
	 * Constructor
	 *
	 * @param Generator      $generator      the generator object
	 * @param TemplateLoader $templateLoader the template loader
	 * @param \Closure       $compiler       the compiler function
	 * @param array          $partials       the partial name resolving map
	 */
	public function __construct(
		Generator $generator,
		TemplateLoader $templateLoader,
		array $patrials = null
	)
	{
		$this->generator      = $generator;
		$this->templateLoader = $templateLoader;
		$this->partials       = (array) $patrials;
		$this->compiler       = function ($generated, $context, $renderer) {
			eval($generated);
			return $result;
		};
	}

	/**
	 * renders the given template
	 *
	 * @param string $template the template name
	 *
	 * @return string
	 */
	public function renderTemplate($template, $context)
	{
		$template  = $this->templateLoader->loadTemplate($template);
		$generated = $this->generator->generate($template);
		$compiler  = $this->compiler;

		return $compiler($generated, $context, $this);
	}

	/**
	 * renders the given partial
	 *
	 * @param string $partial the partial name
	 *
	 * @return string
	 */
	public function renderPartial($partial, $context)
	{
		if (isset($this->partials[$partial])) {
			$partial = $this->partials[$partial];
		}

		return $this->renderTemplate($partial, $context);
	}
}

?>