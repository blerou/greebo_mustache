<?php

class PartialsView
{
	public $name = 'ilmich';
	public $data = array(
		array('name' => 'federica', 'age' => 27, 'gender' => 'female'),
		array('name' => 'marco', 'age' => 32, 'gender' => 'male'),
	);
}

class Partials extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    $partials = array(
      'children' => 'partials/children',
    );
    parent::__construct($template, new PartialsView(), $partials);
  }
}
