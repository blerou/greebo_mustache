<?php

class UTF8View
{
	public $test = '中文又来啦';
}

class UTF8 extends \greebo\test\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new UTF8View());
  }
}