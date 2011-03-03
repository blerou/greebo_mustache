<?php

class PartialsWithViewClass extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    $view       = new StdClass();
		$view->name = 'ilmich';
		$view->data = array(
			array('name' => 'federica', 'age' => 27, 'gender' => 'female'),
			array('name' => 'marco', 'age' => 32, 'gender' => 'male'),
		);

		$partials = array(
			'children' => 'partials_with_view_class/children',
		);

		parent::__construct($template, $view, $partials);
  }
}