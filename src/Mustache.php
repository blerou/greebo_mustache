<?php

namespace Greebo\Mustache;

class Mustache
{
  private $templatePath = array();
  private $suffix = '.mustache';
  private $partialRecursions = 0;

  public function render($template, $view, $partials = null)
  {
    $generated  = $this->compile($template, $partials);
    $compileFor = function($context) use($generated) {
      eval($generated);
      $stripme = preg_quote('/*__stripme__*/', '/');
      $patterns = array('/^\s*%s\n/', '/^\s*%s/', '/\s*%s$/', '/\n\s*%s/', '/%s/');
      foreach ($patterns as $pattern) {
        $result = preg_replace(sprintf($pattern, $stripme), '', $result);
      }
      return $result;
    };

    return $compileFor(new ContextStack($view));
  }

  private function compile($template, $partials)
  {
    $this->partialRecursions++;
    if ($this->partialRecursions > 10) {
      $this->partialRecursions--;
      return '';
    }

    if ($this->isTemplateFile($template)) {
      $templatePath = $this->findTemplate($template);
      if (empty($templatePath)) {
        $this->partialRecursions--;
        return '';
      }

      $template = file_get_contents($templatePath);
    }

    $tokens    = $this->tokenize($template);
    $generated = $this->generate($tokens, $partials);

    $this->partialRecursions--;

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

  private function tokenize($template, $otag   = '{{', $ctag   = '}}')
  {
    $tokens = array();
    while (true) {
      $tagPattern = sprintf('/(%s.+?\\}?%s)/s', preg_quote($otag, '/'), preg_quote($ctag, '/'));
      list($content, $tag, $template) = preg_split($tagPattern, $template, 2, PREG_SPLIT_DELIM_CAPTURE);

      $tokens[] = array('type' => 'content', 'content' => $content);
      if (!isset($tag)) {
        break;
      }

      $tagToken = $this->createTagToken($tag, $otag, $ctag);
      if ($tagToken['type'] == 'section') {
        $sectionPattern = sprintf('/(%s\\/%s%s)/s', preg_quote($otag, '/'), $tagToken['name'], preg_quote($ctag, '/'));
        list($content, $tag, $template) = preg_split($sectionPattern, $template, 2, PREG_SPLIT_DELIM_CAPTURE);

        $tokens[] = $tagToken;
        $tokens   = array_merge($tokens, $this->tokenize($content, $otag, $ctag));
        $tokens[] = array('type' => 'end', 'related' => 'section', 'name' => $tagToken['name']);
      } else if ($tagToken['type'] == 'delimiter') {
        $otag = $tagToken['otag'];
        $ctag = $tagToken['ctag'];
        $tokens[] = $tagToken;
      } else {
        $tokens[] = $tagToken;
      }
    }

    return $tokens;
  }

  private function createTagToken($tag, $otag, $ctag)
  {
    $tag = substr($tag, strlen($otag), -1*strlen($ctag));
    switch (substr($tag, 0, 1)) {
      case '#':
        return array('type' => 'section', 'name' => trim(substr($tag, 1)));
      case '/':
        return array('type' => 'end', 'related' => 'inverted_section', 'name' => trim(substr($tag, 1)));
      case '^':
        return array('type' => 'inverted_section', 'name' => trim(substr($tag, 1)));
      case '{':
        return array('type' => 'raw_variable', 'name' => trim(substr($tag, 1, -1)));
      case '&':
        return array('type' => 'raw_variable', 'name' => trim(substr($tag, 1)));
      case '!':
        return array('type' => 'comment');
      case '=':
        list($otag, $ctag) = preg_split('/ +/', trim($tag, ' ='));
        return array('type' => 'delimiter', 'otag' => $otag, 'ctag' => $ctag);
      case '>':
        return array('type' => 'partial', 'name' => trim(substr($tag, 1)));
      default:
        return array('type' => 'variable', 'name' => $tag);
    }
  }

  private function generate($tokens, $partials)
  {
    if ($this->partialRecursions == 1) {
      $compiled = '$result = "";';
    } else {
      $compiled = '$result .= "";';
    }
    foreach ($tokens as $token) {
      $compiled .= "\n";
      $compiled .= $this->generateForToken($token, $partials);
    }
    return $compiled;
  }

  private function generateForToken($token, $partials)
  {
    static $stripStartingNewLine = false;
    switch ($token['type']) {
      case 'content':
        $content = strtr($token['content'], '"', '\\"');
        if ($stripStartingNewLine) {
          $content = '/*__stripme__*/'.$content;
        }
        $stripStartingNewLine = false;
        return sprintf('$result .= "%s";', $content);
      case 'variable':
        $stripStartingNewLine = false;
        return sprintf('$result .= $context->get(\'%s\');', $token['name']);
      case 'raw_variable':
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
          array('%name%' => $token['name'])
        );
      case 'inverted_section':
        $stripStartingNewLine = true;
        return strtr(
          '$_%name% = $context->getRaw(\'%name%\');
           if (empty($_%name%)) {',
          array('%name%' => $token['name'])
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
              array('%name%' => $token['name'])
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
        return $this->compile($partialName, $partials);
      default:
        $stripStartingNewLine = false;
        return '';
    }
  }

  public function addTemplatePath($path)
  {
    $this->templatePath[] = rtrim($path, '/\\');
  }

  public function setSuffix($suffix)
  {
    $this->suffix = '.'.ltrim($suffix, '.');
  }
}

class ContextStack
{
  private $stack = array();

  public function __construct($view, $escaper = null)
  {
    $this->push($view);
    if (empty($escaper) || !is_callable($escaper)) {
      $escaper = function($value) { return htmlentities($value, ENT_COMPAT, 'UTF-8'); };
    }
    $this->escaper = $escaper;
  }

  public function get($name)
  {
    $value = $this->getRaw($name);

    if (is_scalar($value)) {
      return call_user_func($this->escaper, $value);
    }

    return $value;
  }

  public function getRaw($name)
  {
    foreach ($this->stack as $view) {
      if (is_array($view) && isset($view[$name])) {
        return $view[$name];
      } else if (is_object($view) && method_exists($view, $name)) {
        return $view->$name();
      } else if (is_object($view) && property_exists($view, $name)) {
        return $view->$name;
      }
    }
    // no variable found
    return null;
  }

  public function push($view)
  {
    array_unshift($this->stack, $view);
  }

  public function pop()
  {
    return array_shift($this->stack);
  }

  public function iterable($var)
  {
    if ($var instanceof \Traversable) {
      return true;
    }
    if (!is_array($var)) {
      return false;
    }
    if (empty($var)) {
      return true;
    }
    $textKeys = array_filter(array_keys($var), 'is_string');

    return empty($textKeys);
  }
}