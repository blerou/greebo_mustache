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

namespace GreeboTest\Mustache;

use Greebo\Mustache\Mustache;

class ExamplesTest extends \PHPUnit_Framework_TestCase
{
  public function setUp()
  {
    $this->mustache = new Mustache();
  }

	/**
   * @test
	 * @group interpolation
	 */
	public function renderWithData()
  {
    $result = $this->mustache->render('{{first_name}} {{last_name}}', array('first_name' => 'Charlie', 'last_name' => 'Chaplin'));
		$this->assertEquals('Charlie Chaplin', $result);
    $result = $this->mustache->render('{{last_name}}, {{first_name}}', array('first_name' => 'Frank', 'last_name' => 'Zappa'));
		$this->assertEquals('Zappa, Frank', $result);
	}

	/**
   * @test
	 * @group partials
	 */
	public function renderWithPartials()
  {
    $result = $this->mustache->render('{{>stache}}', array('first_name' => 'Charlie', 'last_name' => 'Chaplin'), array('stache' => '{{first_name}} {{last_name}}'));
		$this->assertEquals('Charlie Chaplin', $result);
    $result = $this->mustache->render('{{last_name}}, {{first_name}}', array('first_name' => 'Frank', 'last_name' => 'Zappa'));
		$this->assertEquals('Zappa, Frank', $result);
	}

	/**
   * @test
	 * @group comments
	 */
	public function mustacheShouldAllowNewlinesInCommentsAndAllOtherTags()
  {
		$this->assertEquals('', $this->mustache->render("{{! comment \n \t still a comment... }}"));
	}

	/**
   * @test
	 * @group examples
	 * @dataProvider examplesData
	 */
	public function examples($class, $template, $output)
  {
    $interestings = array('Complex', 'SectionsNested');
//    if (!in_array($class, $interestings)) return;
		$renderTrigger = new $class($template);
		$this->assertEquals($output, $renderTrigger->__trigger_render($this->mustache));
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
		$basedir = dirname(__FILE__) . '/../examples/';

		$cases = array();

		$files = new \RecursiveDirectoryIterator($basedir);
		while ($files->valid()) {
      $example = $files->getSubPathname();
      $unimplemented = array('implicit_iterator', 'dot_notation', 'pragma_unescaped', 'pragmas_in_partials');
      if (\in_array($example, $unimplemented)) {
        $files->next();
        continue;
      }

			if ($files->hasChildren() && $children = $files->getChildren()) {
				$class    = null;
				$template = null;
				$output   = null;
      
				foreach ($children as $file) {
					if (!$file->isFile()) continue;

					$filename = $file->getPathname();
					$info = pathinfo($filename);

					if (isset($info['extension'])) {
						switch($info['extension']) {
							case 'php':
								$class = $info['filename'];
								include_once($filename);
								break;

							case 'mustache':
								$template = file_get_contents($filename);
								break;

							case 'txt':
								$output = file_get_contents($filename);
								break;
						}
					}
				}

				if (!empty($class)) {
					$cases[$example] = array($class, $template, $output);
				}
			}

			$files->next();
		}
		return $cases;
	}

	/**
   * @test
	 * @group delimiters
   * @dataProvider delimitersData
	 */
	public function crazyDelimiters($template, $view)
  {
		$this->assertEquals('success', $this->mustache->render($template, $view));
	}

  public function delimitersData()
  {
    return array(
      array('{{=[[ ]]=}}[[ result ]]', array('result' => 'success')),
		  array('{{=(( ))=}}(( result ))', array('result' => 'success')),
		  array('{{={$ $}=}}{$ result $}', array('result' => 'success')),
		  array('{{=<.. ..>=}}<.. result ..>', array('result' => 'success')),
		  array('{{=^^ ^^}}^^ result ^^', array('result' => 'success')),
		  array('{{=// \\\\}}// result \\\\', array('result' => 'success')),
    );
  }

	/**
   * @test
	 * @group delimiters
	 */
	public function resetDelimiters()
  {
		$this->assertEquals('success', $this->mustache->render('{{=[[ ]]=}}[[ result ]]', array('result' => 'success')));
		$this->assertEquals('success', $this->mustache->render('{{=<< >>=}}<< result >>', array('result' => 'success')));
		$this->assertEquals('success', $this->mustache->render('{{=<% %>=}}<% result %>', array('result' => 'success')));
	}

	/**
   * @_test
	 * @group sections
	 * @dataProvider poorlyNestedSectionsData
	 * @expectedException MustacheException
	 */
	public function poorlyNestedSections($template)
  {
		$this->mustache->render($template);
	}

	public function poorlyNestedSectionsData()
  {
		return array(
			array('{{#foo}}'),
			array('{{#foo}}{{/bar}}'),
			array('{{#foo}}{{#bar}}{{/foo}}'),
			array('{{#foo}}{{#bar}}{{/foo}}{{/bar}}'),
			array('{{#foo}}{{/bar}}{{/foo}}'),
		);
	}

	/**
   * @test
	 * @group sections
	 */
	public function mustacheInjection()
  {
		$template = '{{#foo}}{{bar}}{{/foo}}';
		$view = array(
			'foo' => true,
			'bar' => '{{win}}',
			'win' => 'FAIL',
		);

		$this->assertEquals('{{win}}', $this->mustache->render($template, $view));
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