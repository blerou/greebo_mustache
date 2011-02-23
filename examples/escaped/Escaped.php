<?php

class EscapedView
{
	public $title = '"Bear" > "Shark"';
}

class Escaped extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new EscapedView());
  }
}