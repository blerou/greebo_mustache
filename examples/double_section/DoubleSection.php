<?php

class DoubleSectionView
{
	public function t() {
		return true;
	}

	public $two = "second";
}

class DoubleSection extends \GreeboTest\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new DoubleSectionView());
  }
}