<?php

class UTF8UnescapedView
{
	public $test = '中文又来啦';
}

class UTF8Unescaped extends \greebo\test\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new UTF8UnescapedView());
  }
}