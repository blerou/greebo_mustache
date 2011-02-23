<?php

/**
 * DotNotation example class. Uses DOT_NOTATION pragma.
 *
 * @extends \greebo\test\mustache\RenderTestTrigger
 */
class DotNotation extends \greebo\test\mustache\RenderTestTrigger {
	public $person = array(
		'name' => array('first' => 'Chris', 'last' => 'Firescythe'),
		'age' => 24,
		'hometown' => array(
			'city'  => 'Cincinnati',
			'state' => 'OH',
		)
	);

	public $normal = 'Normal';
}
