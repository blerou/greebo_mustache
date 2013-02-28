<?php

class EscapedView
{
	public $title = '"Bear" > "Shark"';
}

class Escaped extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new EscapedView());
  }
}