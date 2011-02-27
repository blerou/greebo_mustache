<?php
/**
 * This file is part of greebo mustache
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace greebo\mustache;

/**
 * Tokenizer class
 *
 * @author blerou <sulik.szabolcs@gmail.com>
 */
class Tokenizer
{
	/**
	 * @var array stack for sections
	 */
	private $sectionStack;

	/**
	 * tokenize the template
	 *
	 * @param string $template the template
	 * @param string $otag     the open tag
	 * @param string $ctag     the clode tag
	 *
	 * @return array
	 */
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

		if (!empty($this->sectionStack)) {
			throw new Exception('missing close tag for section "'.$this->sectionStack[0]['name'].'"');
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

?>