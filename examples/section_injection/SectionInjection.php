<?php

class SectionInjectionView
{
	public $foo = true;
	public $bar = '{{win}}';
	public $win = 'FAIL';
}

class SectionInjection extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new SectionInjectionView());
  }
}