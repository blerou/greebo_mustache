<?php
/**
 * greeboo_mustache
 *
 * @category   Test
 * @package    greeboo_mustache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license    http://www.opensource.org/licenses/mit-license.php he MIT License
 */

namespace greebo\mustache;

class ExamplesTest extends \PHPUnit_Framework_TestCase
{
	private static $templatePath = '/../../../examples/';

	public function setUp()
	{
		$templateLoader = new TemplateLoader(array(__DIR__.self::$templatePath));
		$generator      = new JitGenerator(new Tokenizer(), $templateLoader);
		$this->mustache = new Mustache($generator);
	}

	/**
	 * @test
	 * @group examples
	 * @dataProvider examplesData
	 */
	public function examples($class, $template, $expected)
	{
		$renderTrigger = new $class($template);
		$message       = "{$class} ({$template}) rendered poorly";

		$this->assertEquals($expected, $renderTrigger->__trigger_render($this->mustache), $message);
	}

	/**
	 * Data provider for testExamples method.
	 *
	 * Assumes that an `examples` directory exists inside parent directory.
	 * This examples directory should contain any number of subdirectories, each of which contains
	 * three files: one Mustache class (.php), one Mustache template (.mustache), and one output file
	 * (.txt).
	 *
	 * This whole mess will be refined later to be more intuitive and less prescriptive, but it'll
	 * do for now. Especially since it means we can have unit tests :)
	 *
	 * @return array
	 */
	public function examplesData()
	{
		$cases   = array();
		$baseDir = \realpath(__DIR__.self::$templatePath);
		$files   = new \RecursiveDirectoryIterator($baseDir, \RecursiveDirectoryIterator::SKIP_DOTS);
		foreach ($files as $dir) {
			$dirPath = $dir->getRealPath();
			if (!is_dir($dirPath)) continue;

			$dirName = $dir->getBasename();

			$unimplemented = array('implicit_iterator', 'dot_notation', 'pragma_unescaped', 'pragmas_in_partials');
			if (\in_array($dirName, $unimplemented)) continue;

			$className = \str_replace('_', ' ', $dirName);
			$className = \ucwords($className);
			$className = \str_replace(' ', '', $className);

			$classPath = "{$dirPath}/{$className}.php";
			if (!file_exists($classPath)) continue;

			$template = "{$dirName}/{$dirName}";
			$expected = file_get_contents("{$dirPath}/{$dirName}.txt");
			include_once($classPath);

			$cases[$dirName] = array($className, $template, $expected);
		}
		return $cases;
	}

	/**
	 * @test
	 * @group delimiters
	 */
	public function crazyDelimiters()
	{
		$view = array('result' => 'success');
		$this->assertEquals('success', $this->mustache->render('crazy_delimiters/crazy1', $view));
		$this->assertEquals('success', $this->mustache->render('crazy_delimiters/crazy2', $view));
		$this->assertEquals('success', $this->mustache->render('crazy_delimiters/crazy3', $view));
		$this->assertEquals('success', $this->mustache->render('crazy_delimiters/crazy4', $view));
		$this->assertEquals('success', $this->mustache->render('crazy_delimiters/crazy5', $view));
		$this->assertEquals('success', $this->mustache->render('crazy_delimiters/crazy6', $view));
	}

	/**
	 * @test
	 * @group sections
	 * @expectedException \greebo\mustache\Exception
	 */
	public function poorlyNestedSections()
	{
		$this->assertEquals('success', $this->mustache->render('poorly_nested/template1'));
		$this->assertEquals('success', $this->mustache->render('poorly_nested/template2'));
		$this->assertEquals('success', $this->mustache->render('poorly_nested/template3'));
		$this->assertEquals('success', $this->mustache->render('poorly_nested/template4'));
		$this->assertEquals('success', $this->mustache->render('poorly_nested/template5'));
	}
}

class RenderTestTrigger
{
	public function __construct($template, $view = null, $partials = null)
	{
		$this->template = $template;
		$this->view     = $view;
		$this->partials = $partials;
	}

	public function __trigger_render($mustache)
	{
		return $mustache->render($this->template, $this->view, $this->partials);
	}
}