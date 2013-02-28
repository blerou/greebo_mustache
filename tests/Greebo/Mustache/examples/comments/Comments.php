<?php

class CommentsView
{
  public function title() {
		return 'A Comedy of Errors';
	}
}

class Comments extends \Greebo\Mustache\RenderTestTrigger
{
	public function __construct($template)
  {
    parent::__construct($template, new CommentsView());
  }
}