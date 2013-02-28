<?php
/**
 * This file is part of Greebo Mustache
 *
 * @copyright Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license   http://www.opensource.org/licenses/mit-license.php
 */

namespace Greebo\Mustache;

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
	 * @param string $template
	 * @param string $openTag
	 * @param string $closeTag
	 *
	 * @throws Exception
	 * @return array
	 */
	public function tokenize($template, $openTag = '{{', $closeTag = '}}')
	{
		$this->sectionStack = array();
		$delimiterStack     = array();

		$tokens      = array();
		$lastSection = null;
		while (true) {
			list($content, $tag, $template) = $this->splitNextPart($template, $openTag, $closeTag, $lastSection);

			$tokens[] = array('type' => 'content', 'content' => $content);
			if (!isset($tag)) {
				break;
			}

			$tagToken = $this->createTagToken($tag, $openTag, $closeTag);
			$tokens[] = $tagToken;
			if ($tagToken['type'] == 'section') {
				array_push($delimiterStack, array($openTag, $closeTag));
				$lastSection = $this->createLastSectionEntry($tagToken, $openTag, $closeTag);
			} else if ($tagToken['type'] == 'section_end') {
				list($openTag, $closeTag) = array_pop($delimiterStack);
				$lastSection = empty($this->sectionStack)
					? null
					: $this->createLastSectionEntry($this->sectionStack[0], $openTag, $closeTag);
			} else if ($tagToken['type'] == 'delimiter') {
				$openTag = $tagToken['otag'];
				$closeTag = $tagToken['ctag'];
			}
		}

		if (!empty($this->sectionStack)) {
			throw new Exception('missing close tag for section "'.$this->sectionStack[0]['name'].'"');
		}

		return $tokens;
	}

	private function createLastSectionEntry($token, $openTag, $closeTag)
	{
		return array($token['name'], $openTag, $closeTag);
	}

	private function splitNextPart($template, $openTag, $closeTag, $lastSection)
	{
		$byNextToken = $this->splitByNextToken($template, $openTag, $closeTag);

		if ($lastSection) {
			$lastName      = '\s*\\/\s*'.$lastSection[0].'\s*';
			$byLastSection = $this->splitByNextToken($template, $lastSection[1], $lastSection[2], $lastName);

			$endSectionComesSooner = mb_strlen($byLastSection[0], 'utf-8') < mb_strlen($byNextToken[0], 'utf-8');
			if ($endSectionComesSooner) {
				$result = $byLastSection;
			} else {
				$result = $byNextToken;
			}
		} else {
			$result = $byNextToken;
		}

		return $result + array('', null, null);
	}

	private function splitByNextToken($template, $openTag, $closeTag, $name = '.+?\\}?')
	{
		$tagPattern = sprintf('/(%s%s%s)/s', preg_quote($openTag, '/'), $name, preg_quote($closeTag, '/'));

		return preg_split($tagPattern, $template, 2, PREG_SPLIT_DELIM_CAPTURE);
	}

	private function createTagToken($tag, $openTag, $closeTag)
	{
		$tag = substr($tag, strlen($openTag), -1 * strlen($closeTag));
		switch (substr($tag, 0, 1)) {
			case '#':
				$token = array('type' => 'section', 'name' => trim(substr($tag, 1)));
				array_push($this->sectionStack, $token);
				return $token;
			case '/':
				$sectionName = trim(substr($tag, 1));
				$lastSection = array_pop($this->sectionStack);
				if (empty($lastSection) || $lastSection['name'] != $sectionName) {
					throw new Exception('invalid section close tag: '.$sectionName);
				}
				$type = $lastSection['type'].'_end';
				return array('type' => $type, 'name' => $sectionName);
			case '^':
				$token = array('type' => 'inverted_section', 'name' => trim(substr($tag, 1)));
				array_push($this->sectionStack, $token);
				return $token;
			case '{':
				return array('type' => 'unescaped', 'name' => trim(substr($tag, 1, -1)));
			case '&':
				return array('type' => 'unescaped', 'name' => trim(substr($tag, 1)));
			case '!':
				return array('type' => 'comment');
			case '=':
				list($openTag, $closeTag) = preg_split('/ +/', trim($tag, ' ='));
				return array('type' => 'delimiter', 'otag' => $openTag, 'ctag' => $closeTag);
			case '>':
				return array('type' => 'partial', 'name' => trim(substr($tag, 1)));
			default:
				return array('type' => 'variable', 'name' => trim($tag));
		}
	}
}

?>