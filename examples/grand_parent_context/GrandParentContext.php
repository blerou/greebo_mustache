<?php

class GrandParentContextView
{
	public $grand_parent_id = 'grand_parent1';
	public $parent_contexts = array();
	
	public function __construct()
  {
		$this->parent_contexts[] = array('parent_id' => 'parent1', 'child_contexts' => array(
			array('child_id' => 'parent1-child1'),
			array('child_id' => 'parent1-child2')
		));
		
		$parent2 = new stdClass();
		$parent2->parent_id = 'parent2';
		$parent2->child_contexts = array(
			array('child_id' => 'parent2-child1'),
			array('child_id' => 'parent2-child2')
		);
		
		$this->parent_contexts[] = $parent2;
	}
}

class GrandParentContext extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new GrandParentContextView());
  }
}