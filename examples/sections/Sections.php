<?php

class SectionsView
{
	public $start = "It worked the first time.";

	public function middle() {
		return array(
			array('item' => "And it worked the second time."),
			array('item' => "As well as the third."),
		);
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class Sections extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new SectionsView());
  }
}