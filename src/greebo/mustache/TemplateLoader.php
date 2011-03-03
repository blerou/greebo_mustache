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
	private $extension = 'mustache';

	/**
	 * loads the template with given name
	 *
	 * @param string $template the template name
	 *
	 * @return string
	 * @throws Exception
	 */
	public function loadTemplate($template)
	{
		return file_get_contents($this->findTemplate($template));
	}

	private function findTemplate($template)
	{
		foreach ($this->templatePath as $path) {
			$file = sprintf('%s/%s.%s', $path, $template, $this->extension);
			if (file_exists($file)) {
				return $file;
			}
		}

		throw new Exception();
	}

	/**
	 * adds a new template path to the set
	 *
	 * @param string $templatePath the template path
	 *
	 * @return void
	 */
	public function addTemplatePath($templatePath)
	{
		$this->templatePath[] = $templatePath;
	}

	/**
	 * changes the template extension
	 *
	 * @param string $extension the new extenstion
	 *
	 * @return void
	 */
	public function setExtension($extension)
	{
		$this->extension = ltrim($extension, '.');
	}
}

?>