Greebo Mustache
===============

A [Mustache](http://defunkt.github.com/mustache/) implementation in PHP.


Usage
-----

A quick example:

    <?php
		// simple construct
		$mustache = Mustache::create(TEMPLATE_PATH);

		// advanced construct
		$loader = new TemplateLoader();
		$loader->addTemplatePath(TEMPLATE_PATH);
		$mustache = new Mustache(new JitRenderer($loader));

		echo $mustache->render(TEMPLATE_NAME, array('planet' => 'World!'));
    // "Hello World!"
    ?>


And a more in-depth example--this is the canonical Mustache template (chris.mustache on template path):

    Hello {{name}}
    You have just won ${{value}}!
    {{#in_ca}}
    Well, ${{taxed_value}}, after taxes.
    {{/in_ca}}


Along with the associated Mustache class:

    <?php
    class Chris {
        public $name = "Chris";
        public $value = 10000;
    
        public function taxed_value() {
            return $this->value - ($this->value * 0.4);
        }
    
        public $in_ca = true;
    }


Render it like so:

    <?php
		$mustache = Mustache::create(TEMPLATE_PATH_TO_CHRIS);

		echo $mustache->render('chris', new Chris());
    ?>


It's different
--------------

Other mustache implementations in PHP parses and interpret templates on-the-fly. It's a very time consuming task.
This implementation "generates" PHP template from .mustache file and then run in the given "context".



See Also
--------

 * [Mustache.php - the first implementation](https://github.com/bobthecow/mustache.php)
 * [phly_mustache - Matthew Weier O'Phinney's implementation, in object oriented way](https://github.com/weierophinney/phly_mustache)
 * [Readme for the Ruby Mustache implementation](http://github.com/defunkt/mustache/blob/master/README.md).
 * [mustache(1)](http://defunkt.github.com/mustache/mustache.1.html) and [mustache(5)](http://defunkt.github.com/mustache/mustache.5.html) man pages.