<?php
/**
 * This file is part of Greebo Mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace Greebo\Mustache;

/**
 * Mustache class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class Mustache
{
	/**
	 * @var Renderer
	 */
	private $renderer;

	/**
	 * Constructor
	 *
	 * @param Renderer $renderer
	 */
	public function __construct(Renderer $renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * create default implementation
	 *
	 * @param array  $templatePaths
	 * @param string $extension
	 *
	 * @return Mustache
	 */
	public static function create($templatePaths, $extension = null)
	{
		$loader = new TemplateLoader();
		foreach ((array)$templatePaths as $templatePath)
			$loader->addTemplatePath($templatePath);
		if ($extension)
			$loader->setExtension($extension);

		return new Mustache(new JitRenderer($loader));
	}

	/**
	 * renders the template in the given context
	 *
	 * @param string $template
	 * @param mixed  $view
	 * @param array  $partials
	 *
	 * @return string
	 */
	public function render($template, $view = null, array $partials = null)
	{
		$context = new ContextStack();
		$context->push($view);

		return $this->renderer->withPartials($partials)->renderTemplate($template, $context);
	}
}

?>