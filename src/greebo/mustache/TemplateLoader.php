<?php
/**
 * This file is part of greebo mustache
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * TemplateLoader class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class TemplateLoader
{
	/**
	 * @var array the list of template paths
	 */
	private $templatePath;

	/**
	 * @var string the template extension
	 */
	private $suffix;

	public function __construct(array $templatePaths = array(), $suffix = 'mustache')
	{
		$this->templatePath = $templatePaths;
		$this->suffix       = '.' . ltrim($suffix, '.');
	}

	/**
	 * loads the template with given name
	 *
	 * @param string $template the template name
	 *
	 * @return string
	 */
	public function loadTemplate($template)
	{
		if ($this->isTemplateFile($template)) {
			$templatePath = $this->findTemplate($template);
			if (!empty($templatePath)) {
				$template = file_get_contents($templatePath);
			}
		}

		return $template;
	}

	private function isTemplateFile($template)
	{
		return false === strpos($template, '{{');
	}

	private function findTemplate($template)
	{
		foreach ($this->templatePath as $path) {
			$file = sprintf('%s/%s%s', $path, $template, $this->suffix);
			if (file_exists($file)) {
				return $file;
			}
		}
		return false;
	}
}

?>