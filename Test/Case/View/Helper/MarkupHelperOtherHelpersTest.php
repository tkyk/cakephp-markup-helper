<?php

App::uses('View', 'View');
App::uses('Controller', 'Controller');
App::uses('MarkupHelper', 'Markup.View/Helper');

function _s($obj) {
	return strval($obj);
}

class MarkupHelperOtherHelpersTestCase extends CakeTestCase {
	public $h;
	public $v;
	protected $_dummyViewFile = 'dummy.ctp';

	public function setUp() {
		parent::setUp();
		$this->v = $this->_createView();
		$this->h = new MarkupHelper($this->v);
	}

	public function tearDown() {
		unset($this->h);
		unset($this->v);
		parent::tearDown();
	}

	public function _createView() {
		$c = new Controller;
		$v = new View($c);
		$v->Helpers = $this->getMock('HelperCollection', array('enabled'), array($v));
		return $v;
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
		$this->assertEqual(1, preg_match($p, $x, $m));
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
		$v = $this->v;

		$v->Helpers->expects($this->once())
			->method('enabled')
			->will($this->returnValue(array('Html', 'FooBar')));
		$v->Helpers->Html = $this->getMock('Object');
		$v->Helpers->FooBar = $this->getMock('Object');

		$h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

		// execute beforeRender callback
		$h->beforeRender($this->_dummyViewFile);

		$args = array('label', '/path');

		$dispatch = array('link', $args);
		$v->Helpers->Html
			->expects($this->once())
			->method('dispatchMethod')
			->with($dispatch[0], $dispatch[1])
			->will($this->returnValue('<a>'));
		$this->assertEqual('<a>',
			$h->callHelperMethod('Html', 'link', $args));


		$dispatch = array('test_method', $args);
		$v->Helpers->FooBar
			->expects($this->once())
			->method('dispatchMethod')
			->with($dispatch[0], $dispatch[1])
			->will($this->returnValue('test return'));
		$this->assertEqual('test return',
			$h->callHelperMethod('fb', 'test_method', $args));
	}

	protected function expectOnce($mock, $method, $args, $returnValue) {
		$r = $mock->expects($this->once())
			->method($method);
		call_user_func_array(array($r, 'with'), $args)
			->will($this->returnValue($returnValue));
	}

	public function test__CallHelperMethods() {
		$h = $this->h;
		$v = $this->v;

		$v->Helpers->expects($this->once())
			->method('enabled')
			->will($this->returnValue(array('Html', 'FooBar')));
		$v->Helpers->Html = $this->getMock('Object');
		$v->Helpers->FooBar = $this->getMock('Object');

		$h->useHelper(array('name' => 'FooBar', 'prefix' => 'fb'));

		// execute beforeRender callback
		$h->beforeRender($this->_dummyViewFile);

		$args = array('label', '/path');

		$dispatch = array('link', $args);
		$this->expectOnce($v->Helpers->Html, 'dispatchMethod', $dispatch, '<a>');
		$this->assertIdentical($h, $h->Html_link($args[0], $args[1]));
		$this->assertEqual('<a>', _s($h));

		$dispatch = array('test_method', $args);
		$this->expectOnce($v->Helpers->FooBar, 'dispatchMethod', $dispatch, '<test /><return />');
		$this->assertIdentical($h, $h->fb_test_method($args[0], $args[1]));
		$this->assertEqual('<test /><return />', _s($h));

		$this->assertIdentical($h, $h->Unknown_test_method("a", "b"));
		$this->assertEqual('<Unknown_test_method class="a">b</Unknown_test_method>', _s($h));
	}

	public function testConstructor() {
		$h = new MarkupHelper($this->v);

		$this->assertEqual(2, count($h->helpers));
		$this->assertTrue(in_array('Html', $h->helpers));
		$this->assertTrue(in_array('Form', $h->helpers));
		$this->assertEqual('Html', $h->prefix2Helper['h']);
		$this->assertEqual('Form', $h->prefix2Helper['f']);

		$h2 = new MarkupHelper($this->v,
			array(
				'helpers' => array('Foo',
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
		$v = $this->getMockBuilder('View')
			->disableOriginalConstructor()
			->getMock();
		$h = new MarkupHelper($v);

		// skipping beforeRender to skip the setup for HelperCollection
		//$h->beforeRender($this->_dummyViewFile);

		$elm1 = array('element1');
		$elm2 = array('element2', array('var' => true));

		$v->expects($this->at(0))
			->method('dispatchMethod')
			->with('element', $elm1)
			->will($this->returnValue("<element1 />"));
		$v->expects($this->at(1))
			->method('dispatchMethod')
			->with('element', $elm2)
			->will($this->returnValue("<element2 />"));

		$this->assertEqual("<element1 />", _s($h->renderElement($elm1[0])));
		$this->assertEqual("<element2 />", _s($h->renderElement($elm2[0], $elm2[1])));
	}

	public function testRenderElement_context() {
		$className = get_class($this).uniqid()."TestView";
		$code = '
			class '. $className .' extends View {
				public $h;
				public function element($e, $data = array(), $options = array()) {
					return strval($this->h->p->text($e)->endAllTags);
				}
			}';
		eval($code);

		$v = new $className(null);
		$h = new MarkupHelper($v);
		$v->h = $h;

		$h->beforeRender($this->_dummyViewFile);

		$this->assertEqual('<div>', _s($h->div));
		$this->assertEqual(
			'<p>element1</p>',
			_s($h->renderElement('element1')
		));
		$this->assertEqual('</div>', _s($h->end));

		$this->assertEqual(
			'<div class="a"><p>element2</p></div>',
			_s($h->div("a")->renderElement('element2')->enddiv)
		);
	}

}
