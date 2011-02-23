<?php

class SectionMagicObjectsView
{
	public $start = "It worked the first time.";

	public function middle() {
		return new MagicObjectView();
	}

	public $final = "Then, surprisingly, it worked the final time.";
}

class MagicObjectView
{
	private $_data = array(
		'foo' => 'And it worked the second time.',
		'bar' => 'As well as the third.'
	);

	public function __get($key) {
		return isset($this->_data[$key]) ? $this->_data[$key] : NULL;
	}

	public function __isset($key) {
		return isset($this->_data[$key]);
	}
}

class SectionMagicObjects extends \greebo\mustache\RenderTestTrigger
{
  public function __construct($template)
  {
    parent::__construct($template, new SectionMagicObjectsView());
  }
}