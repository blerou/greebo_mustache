<?php
/**
 * This file is part of Greebo Mustache
 *
 * Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 *
 * @license http://www.opensource.org/licenses/mit-license.php
 */

namespace Greebo\Mustache;

/**
 * Generator class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class Generator
{
	/**
	 * @var Tokenizer the tokenizer object
	 */
	private $tokenizer;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->tokenizer = new Tokenizer();
	}

	/**
	 * generates php template
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function generate($template)
	{
		$tokens   = $this->tokenizer->tokenize($template);
		$compiled = '$result = "";';
		foreach ($tokens as $token) {
			$compiled .= "\n";
			$compiled .= $this->generateForToken($token);
		}

		$compiled .= '
$stripme = \'/*__stripme__*/\';
$pattern = \'/^\\\\s*\'.preg_quote($stripme, \'/\').\'\\\\s*(?:\\\\r\\\\n|\\\\n|\\\\r)/m\';
$result = preg_replace($pattern, \'\', $result);
$result = str_replace($stripme, \'\', $result);
';

		return $compiled;
	}

	private function generateForToken($token)
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
  $section = function($_%name%, $context, $renderer) {
    if (!$context->iterable($_%name%)) $_%name% = array($_%name%);
    $result = "";
    foreach ($_%name% as $_item) {
      $context->push($_item);',
					array('%name%' => $this->createVariableName($token['name']))
				);
			case 'section_end':
				$stripStartingNewLine = true;
				return strtr('
      $context->pop();
    }
    return $result;
  };
  $section = $section($_%name%, $context, $renderer);
  if (is_callable($_%name%)) {
    $section = $_%name%($section);
  }
  $result .= $section;
}',
					array('%name%' => $this->createVariableName($token['name']))
				);
			case 'inverted_section':
				$stripStartingNewLine = true;
				return strtr(
					'$_%name% = $context->getRaw(\'%name%\'); if (empty($_%name%)) {',
					array('%name%' => $this->createVariableName($token['name']))
				);
			case 'inverted_section_end':
				$stripStartingNewLine = true;
				return '}';
			case 'delimiter':
				$stripStartingNewLine = true;
				return '';
			case 'partial':
				return sprintf('$result .= $renderer->renderPartial(\'%s\', $context);', $token['name']);
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