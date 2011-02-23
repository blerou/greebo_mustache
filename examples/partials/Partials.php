<?php

class PartialsView
{
	public $name = 'ilmich';
	public $data = array(
		array('name' => 'federica', 'age' => 27, 'gender' => 'female'),
		array('name' => 'marco', 'age' => 32, 'gender' => 'male'),
	);
}

class Partials extends \greebo\test\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    $partials = array(
      'children' => "{{#data}}{{name}} - {{age}} - {{gender}}\n{{/data}}",
    );
    parent::__construct($template, new PartialsView(), $partials);
  }
}

