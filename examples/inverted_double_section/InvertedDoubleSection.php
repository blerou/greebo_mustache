<?php

class InvertedDoubleSectionView
{
	public $t = false;
	public $two = 'second';
}

class InvertedDoubleSection extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new InvertedDoubleSectionView());
  }
}