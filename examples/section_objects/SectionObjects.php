<?php

class SectionObjectsView
{
	public $start = "It worked the first time.";

	public function middle() {
		return new SectionObjectView;
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class SectionObjectView {
	public $foo = 'And it worked the second time.';
	public $bar = 'As well as the third.';
}

class SectionObjects extends \greebo\test\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new SectionObjectsView());
  }
}