<?php

namespace Greebo\Mustache;

class Mustache
{
  private $templatePath = array();

  public function render($template, $view, $partials = null)
  {
    $d = $template == 'template-with-enumerable';
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
      eval(sprintf('%s', $generated));
      return $_result;
    };

    return $compileFor(new ContextStack($view));
  }

  private function isTemplateFile($template)
  {
    return false === strpos($template, '{{');
  }

  private function findTemplate($template)
  {
    $suffix = '.mustache';
    foreach ($this->templatePath as $path) {
      $file = sprintf('%s/%s%s', $path, $template, $suffix);
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
    $flags = PREG_SPLIT_DELIM_CAPTURE;// | PREG_SPLIT_NO_EMPTY;
    $tokenPattern = sprintf('/(%s.+?%s)/', preg_quote($otag, '/'), preg_quote($ctag, '/'));
    return preg_split($tokenPattern, $template, null, $flags);
  }

  private function createToken($part, $otag, $ctag)
  {
    if (0 === strpos($part, $otag)) {
      $part = substr($part, strlen($otag), -1*strlen($ctag));
      switch (substr($part, 0, 1)) {
        case '#':
          return array('type' => 'section_start', 'name' => trim(substr($part, 1)));
        case '/':
          return array('type' => 'section_end', 'name' => trim(substr($part, 1)));
        default:
          return array('type' => 'variable', 'name' => $part);
      }
    } else {
      return array('type' => 'content', 'content' => $part);
    }
    
  }

  private function generate($tokens)
  {
    $compiled = '$_result = "";';
    foreach ($tokens as $token) {
      $compiled .= "\n";
      $compiled .= $this->generateForToken($token);
    }
    return $compiled;
  }

  private function generateForToken($token)
  {
    static $stripStartingNewLine = false;
    switch ($token['type']) {
      case 'content':
        $content = strtr($token['content'], '"', '\\"');
        if ($stripStartingNewLine) {
          $content = preg_replace('/^ *\\n/', '', $content);
        }
        $stripStartingNewLine = false;
        return sprintf('$_result .= "%s";', $content);
      case 'variable':
        $stripStartingNewLine = false;
        return sprintf('$_result .= $context->get(\'%s\');', $token['name']);
      case 'section_start':
        $stripStartingNewLine = true;
        return sprintf('$_ = $context->getRaw(\'%s\'); if ($_) { $context->push($_);', $token['name']);
      case 'section_end':
        $stripStartingNewLine = true;
        return sprintf('$context->pop(); }');
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
    return call_user_func($this->escaper, $this->getRaw($name));
  }

  public function getRaw($name)
  {
    foreach ($this->stack as $view) {
      if (is_array($view) && isset($view[$name])) {
        $value = $view[$name];
      } else if (is_object($view) && method_exists($view, $name)) {
        $value = $view->$name();
      } else if (is_object($view) && isset($view->$name)) {
        $value = $view->$name;
      } else {
        // no variable found
        $value = null;
      }
    }

    return $value;
  }

  public function push($view)
  {
    array_unshift($this->stack, $view);
  }

  public function pop()
  {
    return array_shift($this->stack);
  }

  public function enumerable($name)
  {
    $value = $this->getRaw($name);

    if ($value instanceof Traversable) {
      return true;
    }
    if (!is_array($value)) {
      return false;
    }
    if (empty($value)) {
      return true;
    }
    $textKeys = array_filter(array_keys($value), 'is_string');

    return !empty($textKeys);

  }
}