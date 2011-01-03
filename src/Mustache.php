<?php

namespace Greebo\Mustache;

class Mustache
{
  private $templatePath = array();
  private $suffix = '.mustache';

  public function render($template, $view, $partials = null)
  {
    $d = false;//$template == 'template-with-enumerable';
    if ($this->isTemplateFile($template)) {
      $templatePath = $this->findTemplate($template);
      if (empty($templatePath)) {
        return '';
      }

      $template = file_get_contents($templatePath);
    }

    $tokens     = $this->tokenize($template, '{{', '}}');
    $generated  = $this->generate($tokens);
    if ($d) var_dump($tokens, $generated);
    $compileFor = function($context) use($generated) {
      eval($generated);
      $stripme = preg_quote('/*__stripme__*/', '/');
      $patterns = array('/^\s*%s\n/', '/^\s*%s/', '/\s*%s$/', '/\n\s*%s/');
      foreach ($patterns as $pattern) {
        $result = \preg_replace(sprintf($pattern, $stripme), '', $result);
      }
      return $result;
    };

    return $compileFor(new ContextStack($view));
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

  private function tokenize($template, $otag, $ctag)
  {
    $parts  = $this->splitTemplate($template, $otag, $ctag);
    $tokens = array();
    foreach ($parts as $part) {
      $tokens[] = $this->createToken($part, $otag, $ctag);
    }
    return $tokens;
  }

  private function splitTemplate($template, $otag, $ctag)
  {
    $flags   = PREG_SPLIT_DELIM_CAPTURE;
    $pattern = sprintf('/(%s.+?\\}?%s)/s', preg_quote($otag, '/'), $ctag, preg_quote($ctag, '/'));
    return preg_split($pattern, $template, null, $flags);
  }

  private function createToken($part, $otag, $ctag)
  {
    if (0 === strpos($part, $otag)) {
      $part = substr($part, strlen($otag), -1*strlen($ctag));
      switch (substr($part, 0, 1)) {
        case '#':
          return array('type' => 'section', 'name' => trim(substr($part, 1)));
        case '/':
          return array('type' => 'end', 'name' => trim(substr($part, 1)));
        case '^':
          return array('type' => 'inverted_section', 'name' => trim(substr($part, 1)));
        case '{':
          return array('type' => 'raw_variable', 'name' => trim(substr($part, 1, -1)));
        case '&':
          return array('type' => 'raw_variable', 'name' => trim(substr($part, 1)));
        case '!':
          return array('type' => 'comment');
        default:
          return array('type' => 'variable', 'name' => $part);
      }
    } else {
      return array('type' => 'content', 'content' => $part);
    }
  }

  private function generate($tokens)
  {
    $compiled = '$result = "";';
    foreach ($tokens as $token) {
      $compiled .= "\n";
      $compiled .= $this->generateForToken($token);
    }
    return $compiled;
  }

  private function generateForToken($token)
  {
    static $stripStartingNewLine = false;
    static $endStack = array();
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
        \array_push($endStack, 'section');
        return strtr(
          '$_%name% = $context->getRaw(\'%name%\');
           if ($_%name%) {
             if (!$context->iterable($_%name%)) $_%name% = array($_%name%);
             foreach ($_%name% as $_item) {
               $context->push($_item);',
          array('%name%' => $token['name'])
        );
      case 'inverted_section':
        $stripStartingNewLine = true;
        \array_push($endStack, 'inverted_section');
        return strtr(
          '$_%name% = $context->getRaw(\'%name%\');
           if (empty($_%name%)) {',
          array('%name%' => $token['name'])
        );
      case 'end':
        $stripStartingNewLine = true;
        switch (\array_pop($endStack)) {
          case 'section':
            return '$context->pop();} }';
          case 'inverted_section':
            return '}';
          default:
            return '';
        }
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
    return call_user_func($this->escaper, $value);
  }

  public function getRaw($name)
  {
    foreach ($this->stack as $view) {
      if (is_array($view) && isset($view[$name])) {
        return $view[$name];
      } else if (is_object($view) && method_exists($view, $name)) {
        return $view->$name();
      } else if (is_object($view) && isset($view->$name)) {
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