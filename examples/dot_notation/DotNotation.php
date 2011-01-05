<?php

/**
 * DotNotation example class. Uses DOT_NOTATION pragma.
 *
 * @extends \GreeboTest\Mustache\RenderTestTrigger
 */
class DotNotation extends \GreeboTest\Mustache\RenderTestTrigger {
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
