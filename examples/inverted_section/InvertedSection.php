<?php

class InvertedSectionView
{
	public $repo = array();
}

class InvertedSection extends \greebo\test\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new InvertedSectionView());
  }
}