<?php
/**
 * This file is part of greebo mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * JitRenderer class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class JitRenderer implements Renderer
{
	/**
	 * @var Generator the generator object
	 */
	private $generator;

	/**
	 * @var TemplateLoader the template loader object
	 */
	private $loader;

	/**
	 * @var array the partial definition map
	 */
	private $partials = array();

	/**
	 * @var \Closure the compiler function
	 */
	private $compiler;

	/**
	 * Constructor
	 *
	 * @param TemplateLoader $loader
	 */
	public function __construct(TemplateLoader $loader)
	{
		$this->generator = new Generator();
		$this->loader    = $loader;
		$this->compiler  = function ($generated, $context, $renderer) {
			eval($generated);
			return $result;
		};
	}

	/**
	 * set up partial definition
	 *
	 * @param array $patrials
	 *
	 * @return JitRenderer
	 */
	public function withPartials(array $patrials = null)
	{
		$this->partials = (array) $patrials;
		return $this;
	}

	/**
	 * renders the given template
	 *
	 * @param string       $template
	 * @param ContextStack $context
	 *
	 * @return string
	 */
	public function renderTemplate($template, ContextStack $context)
	{
		$template  = $this->loader->loadTemplate($template);
		$generated = $this->generator->generate($template);
		$compiler  = $this->compiler;

		return $compiler($generated, $context, $this);
	}

	/**
	 * renders the given partial
	 *
	 * @param string       $partial
	 * @param ContextStack $context
	 *
	 * @return string
	 */
	public function renderPartial($partial, ContextStack $context)
	{
		if (isset($this->partials[$partial])) {
			$partial = $this->partials[$partial];
		}

		return $this->renderTemplate($partial, $context);
	}
}

?>