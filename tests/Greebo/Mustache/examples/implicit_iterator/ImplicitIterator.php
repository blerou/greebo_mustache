<?php

class ImplicitIteratorView
{
	public $data = array('Donkey Kong', 'Luigi', 'Mario', 'Peach', 'Yoshi');
}

class ImplicitIterator extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new ImplicitIteratorView());
  }
}