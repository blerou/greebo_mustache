<?php
/**
 * This file is part of greebo mustache
 *
 * Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 *
 * @license http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * JitGenerator class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class JitGenerator implements Generator
{
	/**
	 * @var array stores compiled partial existance
	 */
	private $isPartialExists = array();

	/**
	 * @var array of partial function definition
	 */
	private $partialFunctions = array();

	/**
	 * Constructor
	 *
	 * @param Tokenizer      $tokenizer      the tokenizer
	 * @param TemplateLoader $templateLoader the template loader
	 */
	public function __construct($tokenizer, $templateLoader)
	{
		$this->tokenizer = $tokenizer;
		$this->templateLoader = $templateLoader;
	}

	/**
	 * compiles the given template and use the given partials' definition
	 *
	 * @param string       $template the template
	 * @param array        $partials the partials' definition
	 * @param ContextStack $context  the view context object
	 *
	 * @return string
	 */
	public function compile($template, $partials)
	{
		$template = $this->templateLoader->loadTemplate($template);
		$tokens   = $this->tokenizer->tokenize($template);

		return  $this->generate($tokens, $partials, $context);
	}

	private function generate($tokens, $partials)
	{
		$compiled = '$result = "";';
		foreach ($tokens as $token) {
			$compiled .= "\n";
			$compiled .= $this->generateForToken($token, $partials);
		}

		$compiled  = implode("\n", $this->partialFunctions) . $compiled;
		$compiled .= '
$stripme = \'/*__stripme__*/\';
$pattern = \'/^\\\\s*\'.preg_quote($stripme, \'/\').\'\\\\s*(?:\\\\r\\\\n|\\\\n|\\\\r)/m\';
$result = preg_replace($pattern, \'\', $result);
$result = str_replace($stripme, \'\', $result);
';

		return $compiled;
	}

	private function generateForToken($token, $partials)
	{
		static $stripStartingNewLine = false;
		switch ($token['type']) {
			case 'content':
				$content = str_replace('"', '\\"', $token['content']);
				if ($stripStartingNewLine) {
					$content = '/*__stripme__*/' . $content;
				}
				$stripStartingNewLine = false;
				return $content ? sprintf('$result .= "%s";', $content) : '';
			case 'variable':
				$stripStartingNewLine = false;
				return sprintf('$result .= $context->get(\'%s\');', $token['name']);
			case 'unescaped':
				$stripStartingNewLine = false;
				return sprintf('$result .= $context->getRaw(\'%s\');', $token['name']);
			case 'section':
				$stripStartingNewLine = true;
				return strtr('
$_%name% = $context->getRaw(\'%name%\');
if ($_%name%) {
  $section = function($_%name%, $context) {
    if (!$context->iterable($_%name%)) $_%name% = array($_%name%);
    $result = "";
    foreach ($_%name% as $_item) {
      $context->push($_item);
          ',
					array('%name%' => $this->createVariableName($token['name']))
				);
			case 'inverted_section':
				$stripStartingNewLine = true;
				return strtr(
					'$_%name% = $context->getRaw(\'%name%\'); if (empty($_%name%)) {',
					array('%name%' => $this->createVariableName($token['name']))
				);
			case 'end':
				$stripStartingNewLine = true;
				switch ($token['related']) {
					case 'section':
						return strtr('
      $context->pop();
    }
    return $result;
  };
  $section = $section($_%name%, $context);
  if (is_callable($_%name%)) {
    $section = $_%name%($section);
  }
  $result .= $section;
}
              ',
							array('%name%' => $this->createVariableName($token['name']))
						);
					case 'inverted_section':
						return '}';
					default:
						return '';
				}
			case 'delimiter':
				$stripStartingNewLine = true;
				return '';
			case 'partial':
				$partialName = $token['name'];
				if (isset($partials[$partialName])) {
					$partialName = $partials[$partialName];
				}
				return sprintf('$result .= $context->renderTemplate(\'%s\');', $partialName);
			default:
				$stripStartingNewLine = false;
				return '';
		}
	}

	private function createVariableName($name)
	{
		return preg_replace('/[^a-z0-9_]/i', '_', $name);
	}
}

?>