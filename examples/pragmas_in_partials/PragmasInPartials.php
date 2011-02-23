<?php

class PragmasInPartialsView
{
	public $say = '< RAWR!! >';
}

class PragmasInPartials extends \greebo\test\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    $partials = array(
      'dinosaur' => '{{say}}'
    );
    parent::__construct($template, new PragmasInPartialsView(), $partials);
  }
}