<?php

namespace Greebo\Mustache;

class Mustache
{
  public function render($template, $view, $partials = null)
  {
    $tokens    = $this->tokenize($template, '{{', '}}');
    $generated = $this->generate($tokens);
    $context   = new ContextStack($view);
    $compiler  = function($context) use($generated) {
      ob_start();
      eval(sprintf('?>%s<?php ', $generated));
      return ob_get_clean();
    };

    return $compiler($context);
  }

  private function tokenize($template, $otag, $ctag)
  {
    $parts = $this->splitTemplate($template, $otag, $ctag);

    $tokens = array();
    foreach ($parts as $part) {
      $tokens[] = $this->createToken($part, $otag, $ctag);
    }

    return $tokens;
  }

  private function splitTemplate($template, $otag, $ctag)
  {
    $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
    $tokenPattern = sprintf('/(%s.+?%s)/', preg_quote($otag, '/'), preg_quote($ctag, '/'));

    return preg_split($tokenPattern, $template, null, $flags);
  }

  private function createToken($part, $otag, $ctag)
  {
    if (0 === strpos($part, $otag)) {
      $part = trim($part, $otag.$ctag);
      return array('variable', array('name' => $part));
    } else {
      return array('content', array('content' => $part));
    }
    
  }

  private function generate($tokens)
  {
    $compiled = '';
    foreach ($tokens as $token) {
      $compiled .= $this->compileToken($token);
    }

    return $compiled;
  }

  private function compileToken($token)
  {
    switch ($token[0]) {
      case 'content':
        $result = $token[1]['content'];
        break;
      case 'variable':
        $result = sprintf('<?= $context->get(\'%s\') ?>', $token[1]['name']);
        break;
      default:
        $result = '';
        break;
    }

    return $result;
  }

  public function addTemplatePath($path)
  {
    
  }

  public function setSuffix($suffix)
  {
    
  }
}

class ContextStack
{
  private $stack = array();

  public function __construct($view)
  {
    $this->push($view);
    $this->escaper = function($value) { return htmlentities($value, ENT_QUOTES, 'UTF-8'); };
  }

  public function get($name)
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

    return call_user_func($this->escaper, $value);
  }

  public function push($view)
  {
    array_unshift($this->stack, $view);
  }

  public function pop()
  {
    return array_shift($this->stack);
  }
}