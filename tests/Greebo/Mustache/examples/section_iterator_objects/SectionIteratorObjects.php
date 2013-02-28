<?php

class SectionIteratorObjectsView
{
	public $start = "It worked the first time.";

	public $_data = array(
		array('item' => 'And it worked the second time.'),
		array('item' => 'As well as the third.'),
	);

	public function middle() {
		return new ArrayIterator($this->_data);
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class SectionIteratorObjects extends \Greebo\Mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new SectionIteratorObjectsView());
  }
}