<?php

/**
 * DotNotation example class. Uses DOT_NOTATION pragma.
 *
 * @extends \Greebo\Mustache\RenderTestTrigger
 */
class DotNotation extends \Greebo\Mustache\RenderTestTrigger {
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
