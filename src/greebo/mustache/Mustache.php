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
	/**
	 * @var Generator the template generator object
	 */
	private $generator;

	/**
	 * @var TemplateLoader the template loader object
	 */
	private $templateLoader;

	/**
	 * Constructor
	 *
	 * @param Generator      $generator      the template generator object
	 * @param TemplateLoader $templateLoader the template loader object
	 */
	public function __construct(Generator $generator, $templateLoader)
	{
		$this->generator      = $generator;
		$this->templateLoader = $templateLoader;
	}

	/**
	 * renders the template in the given context
	 *
	 * @param string $template the template name
	 * @param mixed  $view     the view
	 * @param array  $partials the partials
	 *
	 * @return string
	 */
	public function render($template, $view = null, array $partials = null)
	{
		$renderer = new Renderer($this->generator, $this->templateLoader, $partials);
		$context  = new ContextStack($view);

		return $renderer->renderTemplate($template, $context);
	}
}

?>