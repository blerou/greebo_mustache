<?php

class PragmaUnescapedView
{
	public $vs = 'Bear > Shark';
}

class PragmaUnescaped extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new PragmaUnescapedView());
  }
}