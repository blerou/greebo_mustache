<?php

class ChildContext extends \Greebo\Mustache\RenderTestTrigger {

  public function __construct($template)
  {
    $view = array(
      'parent' => array(
        'child' => 'child works',
      ),
      'grandparent' => array(
        'parent' => array(
          'child' => 'grandchild works',
        ),
      )
    );
    parent::__construct($template, $view);
  }
}