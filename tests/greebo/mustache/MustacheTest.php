<?php
/**
 * greeboo mustache tests
 *
 * @copyright  Copyright (c) 2011 Szabolcs Sulik <sulik.szabolcs@gmail.com>
 * @license    http://www.opensource.org/licenses/mit-license.php he MIT License
 */

namespace greebo\mustache;

/**
 * Unit tests for Mustache implementation
 */
class MustacheTest extends \PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		$this->templatePath = array(__DIR__ . '/templates');
		$generator = new JitGenerator(
				new Tokenizer(),
				new TemplateLoader($this->templatePath)
		);
		$this->mustache = new Mustache($generator);
	}

	public function testRendersFileTemplates()
	{
		$view = array(
			'planet' => 'World',
		);
		$test = $this->mustache->render('renders-file-templates', $view);
		$this->assertEquals('Hello World', trim($test));
	}

	public function testCanUseObjectPropertiesForSubstitutions()
	{
		$view = (object) array('planet' => 'World');
		$test = $this->mustache->render('renders-file-templates', $view);
		$this->assertEquals('Hello World', $test);
	}

	public function testCanUseMethodReturnValueForSubstitutions()
	{
		$chris    = new TestAsset\ViewWithMethod;
		$test     = $this->mustache->render('template-with-method-substitution', $chris);
		$expected = <<<EOT
Hello Chris
You have just won \$600000!
EOT;
		$this->assertEquals($expected, trim($test));
	}

	public function testTemplateMayUseConditionals()
	{
		$chris    = new TestAsset\ViewWithMethod;
		$test     = $this->mustache->render('template-with-conditional', $chris);
		$expected = <<<EOT
Hello Chris
You have just won \$1000000!
Well, \$600000, after taxes.

EOT;
		$this->assertEquals($expected, $test);
	}

	public function testConditionalIsSkippedIfValueIsFalse()
	{
		$chris        = new TestAsset\ViewWithMethod;
		$chris->in_ca = false;
		$test         = $this->mustache->render('template-with-conditional', $chris);
		$expected     = <<<EOT
Hello Chris
You have just won \$1000000!

EOT;
		$this->assertEquals($expected, $test);
	}

	public function testConditionalIsSkippedIfValueIsEmpty()
	{
		$chris        = new TestAsset\ViewWithMethod;
		$chris->in_ca = null;
		$test         = $this->mustache->render('template-with-conditional', $chris);
		$expected = <<<EOT
Hello Chris
You have just won \$1000000!

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group iteration
	 */
	public function testTemplateIteratesArrays()
	{
		$view     = new TestAsset\ViewWithArrayEnumerable;
		$test     = $this->mustache->render('template-with-enumerable', $view);
		$expected = <<<EOT
Joe's shopping card:
<ul>
    <li>bananas</li>
    <li>apples</li>
</ul>

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group iteration
	 */
	public function testTemplateIteratesTraversableObjects()
	{
		$view     = new TestAsset\ViewWithTraversableObject;
		$test     = $this->mustache->render('template-with-enumerable', $view);
		$expected = <<<EOT
Joe's shopping card:
<ul>
    <li>bananas</li>
    <li>apples</li>
</ul>

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group higher-order
	 */
	public function testHigherOrderSectionsRenderInsideOut()
	{
		$view     = new TestAsset\ViewWithHigherOrderSection();
		$test     = $this->mustache->render('template-with-higher-order-section', $view);
		$expected = <<<EOT
<b>Hi Tater.</b>
EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group dereference
	 * @group whitespace-issues
	 */
	public function testTemplateWillDereferenceNestedArrays()
	{
		$view = array(
			'a' => array(
				'title' => 'this is an object',
				'description' => 'one of its attributes is a list',
				'list' => array(
					array('label' => 'listitem1'),
					array('label' => 'listitem2'),
				),
			),
		);
		$test     = $this->mustache->render('template-with-dereferencing', $view);
		$expected = <<<EOT
    <h1>this is an object</h1>
    <p>one of its attributes is a list</p>
    <ul>
        <li>listitem1</li>
        <li>listitem2</li>
    </ul>

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group dereference
	 * @group whitespace-issues
	 */
	public function testTemplateWillDereferenceNestedObjects()
	{
		$view     = new TestAsset\ViewWithNestedObjects;
		$test     = $this->mustache->render('template-with-dereferencing', $view);
		$expected = <<<EOT
    <h1>this is an object</h1>
    <p>one of its attributes is a list</p>
    <ul>
        <li>listitem1</li>
        <li>listitem2</li>
    </ul>

EOT;
		$this->assertEquals($expected, $test);
	}

	public function testInvertedSectionsRenderOnEmptyValues()
	{
		$view     = array('repo' => array());
		$test     = $this->mustache->render('template-with-inverted-section', $view);
		$expected = 'No repos';
		$this->assertEquals($expected, trim($test));
	}

	/**
	 * @group partial
	 */
	public function testRendersPartials()
	{
		$view     = new TestAsset\ViewWithObjectForPartial();
		$test     = $this->mustache->render('template-with-partial', $view);
		$expected = 'Welcome, Joe! You just won $1000 (which is $600 after tax)';
		$this->assertEquals($expected, trim($test));
	}

	/**
	 * @group partial
	 */
	public function testAllowsAliasingPartials()
	{
		$view     = new TestAsset\ViewWithObjectForPartial();
		$partials = array('winnings' => 'partial-template');
		$test     = $this->mustache->render('template-with-aliased-partial', $view, $partials);
		$expected = 'Welcome, Joe! You just won $1000 (which is $600 after tax)';
		$this->assertEquals($expected, trim($test));
	}

	public function testEscapesStandardCharacters()
	{
		$view = array('foo' => 't&h\\e"s<e>');
		$test = $this->mustache->render('template-escape', $view);
		$this->assertEquals('t&amp;h\\e&quot;s&lt;e&gt;', $test);
	}

	public function testTripleMustachesPreventEscaping()
	{
		$view = array('foo' => 't&h\\e"s<e>');
		$test = $this->mustache->render('template-unescape', $view);
		$this->assertEquals('t&h\\e"s<e>', $test);
	}

	/**
	 * @group pragma
	 */
	public function testAllowsAlteringBehaviorUsingPragmas()
	{
		$this->markTestIncomplete('Looking for examples of use cases');
	}

	/**
	 * @group pragma
	 */
	public function testHonorsImplicitIteratorPragma()
	{
		$this->renderer->addPragma(new Pragma\ImplicitIterator());
		$view     = array('foo' => array(1, 2, 3, 4, 5, 'french'));
		$test     = $this->mustache->render('template-with-implicit-iterator',$view);
		$expected = <<<EOT

    1
    2
    3
    4
    5
    french

EOT;
		$this->assertEquals($expected, $test);
	}

	public function testAllowsSettingAlternateTemplateSuffix()
	{
		$generator = new JitGenerator(
				new Tokenizer(),
				new TemplateLoader($this->templatePath, 'html')
		);
		$mustache = new Mustache($generator);
		$rendered = $mustache->render('alternate-suffix', array());
		$this->assertContains('alternate template suffix', $rendered);
	}

	public function testStripsCommentsFromRenderedOutput()
	{
		$test     = $this->mustache->render('template-with-comments', array());
		$expected = <<<EOT
First line 
Second line

Third line

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group delim
	 */
	public function testAllowsSpecifyingAlternateDelimiters()
	{
		$view     = array('substitution' => 'working');
		$test     = $this->mustache->render('template-with-delim-set', $view);
		$expected = <<<EOT
This is content, working, from new delimiters.

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group delim
	 */
	public function testAlternateDelimitersSetInSectionOnlyApplyToThatSection()
	{
		$view = array(
			'content' => 'style',
			'section' => array(
				'name' => '-World',
			),
			'postcontent' => 'P.S. Done',
		);
		$test = $this->mustache->render('template-with-delim-set-in-section', $view);
		$expected = <<<EOT
Some text with style
    -World
P.S. Done

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group delim
	 */
	public function testAlternateDelimitersApplyToChildSections()
	{
		$view     = array('content' => 'style', 'substitution' => array('name' => '-World'));
		$test     = $this->mustache->render('template-with-sections-and-delim-set', $view);
		$expected = <<<EOT
Some text with style
    -World

EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group delim
	 */
	public function testAlternateDelimitersDoNotCarryToPartials()
	{
		$view = array(
			'substitution' => 'style',
			'value' => 1000000,
			'taxed_value' => 400000,
		);
		$test = $this->mustache->render('template-with-partials-and-delim-set', $view);
		$expected = <<<EOT
This is content, style, from new delimiters.
You just won $1000000 (which is $400000 after tax)


EOT;
		$this->assertEquals($expected, $test);
	}

	/**
	 * @group pragma
	 */
	public function testPragmasAreSectionSpecific()
	{
		$this->renderer->addPragma(new Pragma\ImplicitIterator());
		$view = array(
			'type' => 'style',
			'section' => array(
				'subsection' => array(1, 2, 3),
			),
			'section2' => array(
				'subsection' => array(1, 2, 3),
			),
		);
		$test = $this->mustache->render('template-with-pragma-in-section', $view);
		$this->assertEquals(1, substr_count($test, '1'), $test);
		$this->assertEquals(1, substr_count($test, '2'), $test);
		$this->assertEquals(1, substr_count($test, '3'), $test);
	}

	/**
	 * @group pragma
	 * @group partial
	 */
	public function testPragmasDoNotExtendToPartials()
	{
		$this->renderer->addPragma(new Pragma\ImplicitIterator());
		$view = array(
			'type' => 'style',
			'section' => array(
				'subsection' => array(1, 2, 3),
			),
		);
		$test = $this->mustache->render('template-with-pragma-and-partial', $view);
		$this->assertEquals(1, substr_count($test, 'Some content, with style'));
		$this->assertEquals(1, substr_count($test, 'This is from the partial'));
		$this->assertEquals(0, substr_count($test, '1'));
		$this->assertEquals(0, substr_count($test, '2'));
		$this->assertEquals(0, substr_count($test, '3'));
	}

	/**
	 * @group partial
	 */
	public function testHandlesRecursivePartials()
	{
		$view = $this->getRecursiveView();
		$test = $this->mustache->render('crazy_recursive', $view);
		foreach (range(1, 6) as $content) {
			$this->assertEquals(1, substr_count($test, $content));
		}
	}

	/**
	 * @group whitespace-issues
	 */
	public function testLexerStripsUnwantedWhitespaceFromTokens()
	{
		$view     = $this->getRecursiveView();
		$test     = $this->mustache->render('crazy_recursive', $view);
		$expected = <<<EOT
<html>
<body>
<ul>
        <li>
    1
    <ul>
            <li>
    2
    <ul>
            <li>
    3
    <ul>
    </ul>
</li>
    </ul>
</li>
            <li>
    4
    <ul>
            <li>
    5
    <ul>
            <li>
    6
    <ul>
    </ul>
</li>
    </ul>
</li>
    </ul>
</li>
    </ul>
</li>
</ul>
</body>
</html>

EOT;
		$this->assertEquals($expected, $test);
	}

	protected function getRecursiveView()
	{
		return array(
			'top_nodes' => array(
				'contents' => '1',
				'children' => array(
					array(
						'contents' => '2',
						'children' => array(
							array(
								'contents' => 3,
								'children' => array(),
							)
						),
					),
					array(
						'contents' => '4',
						'children' => array(
							array(
								'contents' => '5',
								'children' => array(
									array(
										'contents' => '6',
										'children' => array(),
									),
								),
							),
						),
					),
				),
			),
		);
	}
}
