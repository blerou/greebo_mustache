<?php
/**
 * This file is part of greebo mustache.
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * 
 */
class Mustache
{
	private $templatePath = array();
	private $suffix = '.mustache';
	private $partialRecursions = 0;
	private $partials = array();
	private $functions = array();

	public function __construct()
	{
		$this->tokenizer = new Tokenizer();
	}

	public function render($template, $view = null, $partials = null)
	{
		$generated = $this->compile($template, $partials);
		$context   = new ContextStack($view);
		$compile   = function($generated, $context) {
			eval($generated);
			return $result;
		};

		return $compile($generated, $context);
	}

	private function compile($template, $partials)
	{
		if ($this->isTemplateFile($template)) {
			$templatePath = $this->findTemplate($template);
			if (!empty($templatePath)) {
				$template = file_get_contents($templatePath);
			}
		}

		$tokens = $this->tokenizer->tokenize($template);
		$generated = $this->generate($tokens, $partials);

		return $generated;
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

	private function generate($tokens, $partials)
	{
		$compiled = '$result = "";';
		foreach ($tokens as $token) {
			$compiled .= "\n";
			$compiled .= $this->generateForToken($token, $partials);
		}

		$this->partialRecursions++;
		if ($this->partialRecursions == 1) {
			$compiled = implode("\n", $this->functions) . $compiled;
		}
		$this->partialRecursions--;

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
				$partial = $partialName = $token['name'];
				if (isset($partials[$partial])) {
					$partial = $partials[$partial];
				}
				$partialFunction = '__partial_' . $this->createVariableName($partialName);
				if (!isset($this->partials[$partialFunction])) {
					$this->partials[$partialFunction] = 1;
					$this->functions[$partialFunction] = sprintf(
							'if (!function_exists("%s")) { function %s($context) { %s; return $result; } }',
							$partialFunction, $partialFunction, $this->compile($partial, $partials)
					);
				}
				return sprintf('$result .= %s($context);', $partialFunction);
			default:
				$stripStartingNewLine = false;
				return '';
		}
	}

	private function createVariableName($name)
	{
		return \preg_replace('/[^a-z0-9_]/i', '_', $name);
	}

	public function addTemplatePath($path)
	{
		$this->templatePath[] = rtrim($path, '/\\');
	}

	public function setSuffix($suffix)
	{
		$this->suffix = '.' . ltrim($suffix, '.');
	}
}

class Tokenizer
{
	private $sectionStack;

	public function tokenize($template, $otag = '{{', $ctag = '}}')
	{
		$this->sectionStack = array();
		$delimiterStack     = array();

		$tokens      = array();
		$lastSection = null;
		while (true) {
			list($content, $tag, $template) = $this->splitNextPart($template, $otag, $ctag, $lastSection);

			$tokens[] = array('type' => 'content', 'content' => $content);
			if (!isset($tag)) {
				break;
			}

			$tagToken = $this->createTagToken($tag, $otag, $ctag);
			$tokens[] = $tagToken;
			if ($tagToken['type'] == 'section') {
				array_push($delimiterStack, array($otag, $ctag));
				$lastSection = $this->createLastSectionEntry($tagToken, $otag, $ctag);
			} else if ($tagToken['type'] == 'end' && $tagToken['related'] == 'section') {
				list($otag, $ctag) = array_pop($delimiterStack);
				$lastSection = empty($this->sectionStack)
					? null
					: $this->createLastSectionEntry($this->sectionStack[0], $otag, $ctag);
			} else if ($tagToken['type'] == 'delimiter') {
				$otag = $tagToken['otag'];
				$ctag = $tagToken['ctag'];
			}
		}

		return $tokens;
	}

	private function createLastSectionEntry($token, $otag, $ctag)
	{
		return array($token['name'], $otag, $ctag);
	}

	private function splitNextPart($template, $otag, $ctag, $lastSection)
	{
		$byNextToken = $this->splitByNextToken($template, $otag, $ctag);

		if (empty($lastSection)) {
			return $byNextToken;
		}

		$lastName      = '\s*\\/\s*'.$lastSection[0].'\s*';
		$byLastSection = $this->splitByNextToken($template, $lastSection[1], $lastSection[2], $lastName);

		$endSectionComesSooner = mb_strlen($byLastSection[0], 'utf-8') < mb_strlen($byNextToken[0], 'utf-8');
		if ($endSectionComesSooner) {
			return $byLastSection;
		} else {
			return $byNextToken;
		}
	}

	private function splitByNextToken($template, $otag, $ctag, $name = '.+?\\}?')
	{
		$tagPattern = sprintf('/(%s%s%s)/s', preg_quote($otag, '/'), $name, preg_quote($ctag, '/'));

		return preg_split($tagPattern, $template, 2, PREG_SPLIT_DELIM_CAPTURE);
	}

	private function createTagToken($tag, $otag, $ctag)
	{
		$tag = substr($tag, strlen($otag), -1 * strlen($ctag));
		switch (substr($tag, 0, 1)) {
			case '#':
				$token = array('type' => 'section', 'name' => trim(substr($tag, 1)));
				array_unshift($this->sectionStack, $token);
				return $token;
			case '/':
				$sectionName = trim(substr($tag, 1));
				$lastSection = array_shift($this->sectionStack);
				if (empty($lastSection) || $lastSection['name'] != $sectionName) {
					throw new Exception('invalid section close tag');
				}
				return array('type' => 'end', 'related' => $lastSection['type'], 'name' => $sectionName);
			case '^':
				$token = array('type' => 'inverted_section', 'name' => trim(substr($tag, 1)));
				array_unshift($this->sectionStack, $token);
				return $token;
			case '{':
				return array('type' => 'unescaped', 'name' => trim(substr($tag, 1, -1)));
			case '&':
				return array('type' => 'unescaped', 'name' => trim(substr($tag, 1)));
			case '!':
				return array('type' => 'comment');
			case '=':
				list($otag, $ctag) = preg_split('/ +/', trim($tag, ' ='));
				return array('type' => 'delimiter', 'otag' => $otag, 'ctag' => $ctag);
			case '>':
				return array('type' => 'partial', 'name' => trim(substr($tag, 1)));
			default:
				return array('type' => 'variable', 'name' => trim($tag));
		}
	}
}