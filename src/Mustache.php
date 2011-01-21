<?php

namespace Greebo\Mustache;

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
//        $compiledPath = $templatePath.'.php';
//        if (is_file($compiled)) {
//          return file_get_contents($compiledPath);
//        }
        $template = file_get_contents($templatePath);
      }
    }

    $tokens    = $this->tokenizer->tokenize($template);
    $generated = $this->generate($tokens, $partials);
    var_dump($tokens, $generated);

//    if (isset($compiledPath)) {
//      file_put_contents($compiledPath, $generated);
//    }

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
      $compiled = implode("\n", $this->functions).$compiled;
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
        $content = strtr($token['content'], '"', '\\"');
        if ($stripStartingNewLine) {
          $content = '/*__stripme__*/'.$content;
        }
        $stripStartingNewLine = false;
        return $content ? sprintf('$result .= "%s";', $content) : '';
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
        $partialFunction = '__partial_'.$this->createVariableName($partialName);
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
      } else if (is_object($view) && !$view instanceof \Closure && isset($view->$name)) {
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

class Tokenizer
{
  public function tokenize($template, $otag   = '{{', $ctag   = '}}')
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
        $sectionPattern = sprintf('/(%s\\/\\s*%s\\s*%s)/s', preg_quote($otag, '/'), $tagToken['name'], preg_quote($ctag, '/'));
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
        return array('type' => 'variable', 'name' => trim($tag));
    }
  }
}