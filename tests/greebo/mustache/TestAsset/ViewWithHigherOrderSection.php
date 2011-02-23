<?php
/**
 * phly_mustache
 *
 * @category   PhlyTest
 * @package    phly_mustache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2010 Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php New BSD License
 */

/** @namespace */
namespace greebo\mustache\TestAsset;

/**
 * View containing a "higher order" section
 *
 * @category   Phly
 * @package    phly_mustache
 * @subpackage UnitTests
 */
class ViewWithHigherOrderSection
{
    public $name = 'Tater';

    public function bolder()
    {
        return function($text) {
            return '<b>' . $text . '</b>';
        };
    }
}
