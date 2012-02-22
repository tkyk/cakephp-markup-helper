<?php

App::uses('View', 'View');
App::uses('Controller', 'Controller');
App::uses('MarkupHelper', 'Markup.View/Helper');

function _s($obj) {
	return strval($obj);
}

class MarkupHelperOtherHelpersTestCase extends CakeTestCase {
	var $h;

	public function setUp() {
		parent::setUp();
		$this->h = new MarkupHelper($this->_createView());
	}

	public function tearDown() {
		ClassRegistry::flush();
		parent::tearDown();
	}

	public function _createView() {
		$c = new Controller;
		return new View($c, true);
	}

/**
 * - Add HelperName to $helpers
 * - Add (prefix => HelperName) to $prefix2Helper 
 */
	public function testUseHelper() {
		$h = $this->h;

		$helperCount = count($h->helpers);
		$prefixCount = count($h->prefix2Helper);


		$h->useHelper(array('name' => 'Foo'));
		$this->assertTrue(in_array('Foo', $h->helpers));
		$this->assertEqual($helperCount + 1,
			($helperCount = count($h->helpers)));
		$this->assertEqual($prefixCount,
			($prefixCount = count($h->prefix2Helper)));


		$h->useHelper(array('name' => 'Foo',
			'prefix' => 'fff'));
		$this->assertTrue(in_array('Foo', $h->helpers));
		$this->assertEqual('Foo', $h->prefix2Helper['fff']);
		$this->assertEqual($helperCount,
			($helperCount = count($h->helpers)));
		$this->assertEqual($prefixCount + 1,
			($prefixCount = count($h->prefix2Helper)));


		$h->useHelper('Bar');
		$this->assertTrue(in_array('Bar', $h->helpers));
		$this->assertEqual($helperCount + 1,
			($helperCount = count($h->helpers)));
		$this->assertEqual($prefixCount,
			($prefixCount = count($h->prefix2Helper)));
	}

	public function assertHelperPrefixMatch($p, $x, $match1, $match2) {
		$this->assertTrue(preg_match($p, $x, $m));
		$this->assertEqual($match1, $m[1]);
		$this->assertEqual($match2, $m[2]);
	}

	public function testBuildHelperRegex() {
		$h = $this->h;

		$regex = $h->buildHelperRegex(array('Html', 'Form', 'FooBar'));

		$this->assertHelperPrefixMatch($regex, 'Html_link', 'Html', 'link');
		$this->assertHelperPrefixMatch($regex, 'Form_create', 'Form', 'create');
		$this->assertHelperPrefixMatch($regex, 'FooBar_xxx_yyy', 'FooBar', 'xxx_yyy');

		$this->assertNoPattern($regex, 'html_xxxx');
		$this->assertNoPattern($regex, 'Html');
		$this->assertNoPattern($regex, 'Form_');
		$this->assertNoPattern($regex, 'Unknown_');
	}

	public function testBuildHelperRegex_customPrefixes() {
		$h = $this->h;

		$regex = $h->buildHelperRegex(array('FooBar'));

		$this->assertHelperPrefixMatch($regex, 'FooBar_xxx_yyy', 'FooBar', 'xxx_yyy');
		$this->assertNoPattern($regex, 't_xyz');
		$this->assertNoPattern($regex, 'fb_xxx_yyy');

		$regex = $h->buildHelperRegex(array('Test', 'FooBar'),
			array('t', 'fb'));
		$this->assertHelperPrefixMatch($regex, 'FooBar_xxx_yyy', 'FooBar', 'xxx_yyy');
		$this->assertHelperPrefixMatch($regex, 't_xyz', 't', 'xyz');
		$this->assertHelperPrefixMatch($regex, 'fb_xxx_yyy', 'fb', 'xxx_yyy');
		$this->assertNoPattern($regex, 'x_lkjkfdsa');
	}

	public function testCallHelperMethod() {
		$h = $this->h;

		$v = $this->_createView();
		$v->loaded['html'] = new MockHelper();
		$v->loaded['fooBar'] = new MockHelper();
		$h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

		// execute beforeRender callback
		$h->beforeRender($this->_dummyViewFile);

		$args = array('label', '/path');

		$dispatch = array('link', $args);
		$v->loaded['html']->expectOnce('dispatchMethod', $dispatch);
		$v->loaded['html']->setReturnValue('dispatchMethod', '<a>', $dispatch);

		$dispatch = array('test_method', $args);
		$v->loaded['fooBar']->expectOnce('dispatchMethod', $dispatch);
		$v->loaded['fooBar']->setReturnValue('dispatchMethod', 'test return', $dispatch);

		$this->assertEqual('<a>',
			$h->callHelperMethod('Html', 'link', $args));
		$this->assertEqual('test return',
			$h->callHelperMethod('fb', 'test_method', $args));
	}

	public function test__CallHelperMethods() {
		$h = $this->h;

		$v = $this->_createView();
		$v->loaded['html'] = new MockHelper();
		$v->loaded['fooBar'] = new MockHelper();
		$h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

		// execute beforeRender callback
		$h->beforeRender($this->_dummyViewFile);

		$args = array('label', '/path');

		$dispatch = array('link', $args);
		$v->loaded['html']->expectOnce('dispatchMethod', $dispatch);
		$v->loaded['html']->setReturnValue('dispatchMethod', '<a>', $dispatch);

		$dispatch = array('test_method', $args);
		$v->loaded['fooBar']->expectOnce('dispatchMethod', $dispatch);
		$v->loaded['fooBar']->setReturnValue('dispatchMethod', '<test /><return />', $dispatch);

		$this->assertIdentical($h, $h->Html_link($args[0], $args[1]));
		$this->assertEqual('<a>', _s($h));

		$this->assertIdentical($h, $h->fb_test_method($args[0], $args[1]));
		$this->assertEqual('<test /><return />', _s($h));

		$this->assertIdentical($h, $h->Unknown_test_method("a", "b"));
		$this->assertEqual('<Unknown_test_method class="a">b</Unknown_test_method>', _s($h));
	}

	public function testConstructor() {
		$h = new MarkupHelper();

		$this->assertEqual(2, count($h->helpers));
		$this->assertTrue(in_array('Html', $h->helpers));
		$this->assertTrue(in_array('Form', $h->helpers));
		$this->assertEqual('Html', $h->prefix2Helper['h']);
		$this->assertEqual('Form', $h->prefix2Helper['f']);

		$h2 = new MarkupHelper(array('helpers' => array('Foo',
			'Bar' => array('prefix' => 'b'),
			'Zoo' => 'z',
			array('name' => 'Baz', 'prefix' => 'h'))));

		$this->assertEqual(6, count($h2->helpers));
		foreach(array('Html', 'Form', 'Foo', 'Bar', 'Zoo', 'Baz') as $a) {
			$this->assertTrue(in_array($a, $h2->helpers));
		}
		$this->assertEqual('Baz', $h2->prefix2Helper['h']);
		$this->assertEqual('Form', $h2->prefix2Helper['f']);
		$this->assertEqual('Bar', $h2->prefix2Helper['b']);
		$this->assertEqual('Zoo', $h2->prefix2Helper['z']);

	}

	public function testRenderElement() {
		$h = $this->h;

		$v = new MockView();
		ClassRegistry::addObject('view', $v);

		// execute beforeRender
		$h->beforeRender($this->_dummyViewFile);

		$v->expectCallCount('dispatchMethod', 2);
		$v->expectAt(0, 'dispatchMethod', array('element', array('element1')));
		$v->expectAt(1, 'dispatchMethod', array('element', array('element2', array('var' => true))));

		$h->renderElement('element1');
		$h->renderElement('element2', array('var' => true));
	}

	public function testRenderElement_context() {
		$h = $this->h;
		$className = get_class($this).uniqid()."TestView";

		$code = 'class '. $className .' extends View {
			var $h;
			public function __construct($h){ $this->h = $h; }
			public function element($e) {
				return strval($this->h->p->text($e)->endAllTags);
	}
	}';
	eval($code);

	$v = new $className($h);
	ClassRegistry::addObject('view', $v);
	$h->beforeRender($this->_dummyViewFile);

	$this->assertEqual('<div>', _s($h->div));
	$this->assertEqual('<p>element1</p>',
		_s($h->renderElement('element1')));
	$this->assertEqual('</div>', _s($h->end));

	$this->assertEqual('<div class="a"><p>element2</p></div>',
		_s($h->div("a")->renderElement('element2')->enddiv));
	}


	public function testBeforeRender_noRegister() {
		$h = $this->h;
		$h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

		// EmailComponent does not register the view to the ClassRegistry!
		$view = ClassRegistry::getObject('view');
		$this->assertTrue(empty($view));

		// These assignments are done by view
		$h->Html = new MockHelper();
		$h->FooBar = new MockHelper();

		$this->assertEqual(array('Html', 'Form', 'FooBar'),
			$h->helpers);

		// execute beforeRender callback
		$h->beforeRender($this->_dummyViewFile);

		$args = array('label', '/path');

		$dispatch = array('link', $args);
		$h->Html->expectOnce('dispatchMethod', $dispatch);
		$h->Html->setReturnValue('dispatchMethod', '<a>', $dispatch);

		$dispatch = array('test_method', $args);
		$h->FooBar->expectOnce('dispatchMethod', $dispatch);
		$h->FooBar->setReturnValue('dispatchMethod', '<test /><return />', $dispatch);

		$this->assertIdentical($h, $h->Html_link($args[0], $args[1]));
		$this->assertEqual('<a>', _s($h));

		$this->assertIdentical($h, $h->fb_test_method($args[0], $args[1]));
		$this->assertEqual('<test /><return />', _s($h));

		$this->assertIdentical($h, $h->Unknown_test_method("a", "b"));
		$this->assertEqual('<Unknown_test_method class="a">b</Unknown_test_method>', _s($h));        
	}

}
