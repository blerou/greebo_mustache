<?php

class UnescapedView
{
	public $title = "Bear > Shark";
}

class Unescaped extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new UnescapedView());
  }
}