<?php

class ChildContext extends \greebo\test\mustache\RenderTestTrigger {

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