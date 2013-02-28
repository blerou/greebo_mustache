<?php

class SimpleView
{
	public $name = "Chris";
	public $value = 10000;

	public function taxed_value() {
		return $this->value - ($this->value * 0.4);
	}

	public $in_ca = true;
};

class Simple extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new SimpleView());
  }
}