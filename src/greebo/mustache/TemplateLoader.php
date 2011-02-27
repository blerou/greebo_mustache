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
	private $templatePath = array();

	/**
	 * @var string the template extension
	 */
	private $suffix = '.mustache';

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

	/**
	 * adds template path to list
	 *
	 * @param string $path the template path
	 *
	 * @return void
	 */
	public function addTemplatePath($path)
	{
		$this->templatePath[] = rtrim($path, '/\\');
	}

	/**
	 * modifies the extension of the template
	 *
	 * @param string $suffix the new template extension
	 *
	 * @return void
	 */
	public function setSuffix($suffix)
	{
		$this->suffix = '.' . ltrim($suffix, '.');
	}
}

?>