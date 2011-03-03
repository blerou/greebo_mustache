<?php

class RecursivePartialsView
{
	public $name  = 'George';
	public $child = array(
		'name'  => 'Dan',
		'child' => array(
			'name'  => 'Justin',
			'child' => false,
		)
	);
}

class RecursivePartials extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    $partials = array(
      'child' => 'recursive_partials/child',
    );
    parent::__construct($template, new RecursivePartialsView(), $partials);
  }
}