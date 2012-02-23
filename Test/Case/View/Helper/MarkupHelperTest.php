<?php
/**
 * Markup Helper Test file.
 *
 * Copyright (c) 2009 Takayuki Miwa <i@tkyk.name>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) 2009 Takayuki Miwa <i@tkyk.name>
 * @link          http://wp.serpere.info/
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

App::uses('View', 'View');
App::uses('Controller', 'Controller');
App::uses('MarkupHelper', 'Markup.View/Helper');

function _s($obj) {
	return strval($obj);
}

class MarkupTestCase extends CakeTestCase {
	public $v;
	public $h;
	protected $_dummyViewFile = "dummy.ctp";

	public function setUp() {
		parent::setUp();

		$c = new Controller;
		$this->v = new View($c, true);
		$this->h = new MarkupHelper($this->v);

		$this->h->beforeRender($this->_dummyViewFile);
	}

	public function tearDown() {
		ClassRegistry::flush();
		parent::tearDown();
	}

	public function testStartTag() {
		$h = $this->h;

		$this->assertSame($h, $h->startTag('div'));

		$this->assertEquals('<div>', _s($h));
		$this->assertEquals('<div class="css-class">',
			_s($h->startTag('div', 'css-class')));

		$this->assertEquals('<div title="foo" id="bar">',
			_s($h->startTag('div', array('title' => 'foo',
			'id'    => 'bar'))));
		$this->assertEquals('<div id="&lt;&gt;">',
			_s($h->startTag('div', array('id' => '<>'))));

		$this->assertEquals('<div>foo</div>',
			_s($h->startTag('div', null, 'foo')));

		$this->assertEquals('<div title="sample">foo</div>',
			_s($h->startTag('div', array('title' => 'sample'), 'foo')));

		$this->assertEquals('<div title="sample">&lt;foo&gt;</div>',
			_s($h->startTag('div', array('title' => 'sample'), '<foo>')));

		$this->assertEquals('<div title="sample"><foo></div>',
			_s($h->startTag('div', array('title' => 'sample'), '<foo>', false)));

	}

	public function testStartTagChain() {
		$h = $this->h;

		$this->assertEquals('<div class="foo"><div id="bar"><p>baz</p>',
			_s($h->startTag('div', 'foo')
			->startTag('div', array('id' => 'bar'))
			->startTag('p', null, 'baz')));

		$this->assertEquals('<div class="foo"><p class="first">bar</p><p class="second">baz</p>',
			_s($h->startTag('div', 'foo')
			->startTag('p', 'first', 'bar')
			->startTag('p', 'second', 'baz')));

	}

	public function testEndTag() {
		$h = $this->h;

		$this->assertSame($h, $h->endTag());
		$this->assertEquals('', _s($h->endTag()));
		$this->assertEquals('', _s($h->endTag()->endTag()->endTag()));

		$this->assertEquals('<div></div>',
			_s($h->startTag('div')->endTag()));

		$this->assertEquals('<div>foo</div>',
			_s($h->startTag('div', null, 'foo')->endTag()));

		$this->assertEquals('<p><strong><i></i></strong></p>',
			_s($h->startTag('p')
			->startTag('strong')
			->startTag('i')
			->endTag()
			->endTag()
			->endTag()));
	}

	public function testEndTagWithExplicitTagName() {
		$h = $this->h;

		$this->assertEquals('<div></div>',
			_s($h->startTag('div')->endTag('div')));

		$this->assertEquals('<p><strong><i></i></strong></p>',
			_s($h->startTag('p')
			->startTag('strong')
			->startTag('i')
			->endTag('i')
			->endTag('strong')
			->endTag('p')));

		$this->assertEquals('<p><strong><i>closeAll</i></strong></p>',
			_s($h->startTag('p')
			->startTag('strong')
			->startTag('i', null, 'closeAll')
			->endTag('p')));
	}

	protected function _errorHander(&$arr) {
		$self = $this;
		return function($errno, $message) use ($self, &$arr) {
			$arr[] = $message;
		};
	}

	public function assertEndTagError($testCode, $result, $errorMessagePatterns) {
		$errorCount = count($errorMessagePatterns);

		$errors = array();
		set_error_handler($this->_errorHander($errors), E_USER_WARNING);

		$testCode($this->h);
		$this->assertEquals($result, _s($this->h));
		$this->assertEquals($errorCount, count($errors));
		foreach ($errorMessagePatterns as $i => $pattern) {
			$this->assertRegExp($pattern, $errors[$i]);
		}

		restore_error_handler();
	}

	public function testEndTagError() {
		$this->assertEndTagError(
			function($h){ $h->endTag('div'); },
			"",
			array('/unopened tag: div/')
		);

		$this->assertEndTagError(
			function($h){ $h->endTag('div')->endTag('p'); },
			"",
			array('/unopened tag: div/', '/unopened tag: p/')
		);

		$this->assertEndTagError(
			function($h){ $h->startTag('div', null, 'foo')->endTag('div'); },
			'<div>foo</div>',
			array('/unopened tag: div/')
		);

		$this->assertEndTagError(
			function($h){
				$h->startTag('p')
					->startTag('strong')
					->startTag('i')
					->endTag()
					->endTag('div')
					->endTag('p');
			},
			'<p><strong><i></i></strong></p>',
			array('/unopened tag: div/')
		);

		$this->assertEndTagError(
			function($h){
				$h->startTag('p')
					->startTag('strong')
					->startTag('i')
					->endTag('span');
			},
			'<p><strong><i>',
			array('/unopened tag: span/')
		);
	}

	public function testEndAllTags() {
		$h = $this->h;

		$this->assertSame($h, $h->endAllTags());

		$this->assertEquals('', _s($h->endAllTags()));
		$this->assertEquals('<p></p>', _s($h->startTag('p')->endAllTags()));
		$this->assertEquals('<p><strong><i></i></strong></p>',
			_s($h->startTag('p')
			->startTag('strong')
			->startTag('i')
			->endAllTags()));

		$this->assertEquals('<p><strong><i></i></strong></p>',
			_s($h->startTag('p')
			->startTag('strong')
			->startTag('i')
			->endAllTags()
			->endTag()));
	}

	public function testClear() {
		$h = $this->h;

		$h->startTag('div')->startTag('p');
		$h->clear();
		$this->assertEquals('', _s($h));
		$this->assertEquals('', _s($h->endTag()));
	}

	public function testNewline() {
		$h = $this->h;

		$nl = "\n";
		$this->assertSame($h, $h->newline());

		$this->assertEquals($nl, _s($h));
		$this->assertEquals('<div class="foo">'.$nl,
			_s($h->startTag('div', 'foo')->newline()));
		$this->assertEquals('<div class="foo">bar</div>'.$nl,
			_s($h->startTag('div', 'foo', 'bar')->newline()));
		$this->assertEquals('<div class="foo"></div>'.$nl,
			_s($h->startTag('div', 'foo')->endTag()->newline()));
	}

	public function testEmptyElements() {
		$h = $this->h;

		$this->assertEquals("<br />", _s($h->startTag('br')));
		$this->assertEquals("", _s($h->endTag()));

		$this->assertEquals('<p><br /><strong><br /></strong></p>',
			_s($h->startTag('p')
			->startTag('br')
			->startTag('strong')
			->startTag('br')
			->endTag('p')));

		$this->assertEquals('<img src="path.jpg" alt="alt text..." />',
			_s($h->startTag('img', array('src' => 'path.jpg',
			'alt' => 'alt text...'),
			'NEVER USED', false)));

		$this->assertEquals('<hr class="line" />',
			_s($h->startTag('hr', 'line', 'NEVER USED', false)));
	}

	public function testText() {
		$h = $this->h;

		$this->assertSame($h, $h->text("foo"));
		$this->assertEquals("foo", _s($h));
		$this->assertEquals('&lt;foo&gt;&quot;', _s($h->text('<foo>"')));
		$this->assertEquals('<p>foo</p>',
			_s($h->startTag('p')->text('foo')->endTag()));
		$this->assertEquals('<p>foobarzoo</p>',
			_s($h->startTag('p')->text('foo', 'bar', 'zoo')->endTag()));
		$this->assertEquals('<p>foo<strong>bar</strong>baz</p>',
			_s($h->startTag('p')
			->text('foo')
			->startTag('strong')
			->text('bar')
			->endTag()
			->text('baz')
			->endTag()));
	}

	public function testHtml() {
		$h = $this->h;

		$this->assertSame($h, $h->html("foo"));
		$this->assertEquals("foo", _s($h));
		$this->assertEquals('<foo>"', _s($h->html('<foo>"')));
		$this->assertEquals('<p>foo</p>',
			_s($h->startTag('p')->html('foo')->endTag()));
		$this->assertEquals('<p><foo><bar><zoo></p>',
			_s($h->startTag('p')->html('<foo>', '<bar>', '<zoo>')->endTag()));
		$this->assertEquals('<p>foo<strong>bar</strong>baz</p>',
			_s($h->startTag('p')
			->html('foo')
			->html('<strong>bar</strong>')
			->text('baz')
			->endTag()));
	}

	public function testPushAndPopContext() {
		$h = $this->h;

		$h->startTag('div')->startTag('p');

		$h->pushNewContext();
		$this->assertEquals('', _s($h));
		$this->assertEquals('', _s($h->endTag()->endTag()));
		$this->assertEquals('<dl><dt></dt>',
			_s($h->startTag('dl')->startTag('dt')->endTag()));
		$h->pushNewContext();
		$this->assertEquals('', _s($h));
		$this->assertEquals('', _s($h->endTag()));
		$this->assertEquals('<ul><li><span class="foo">',
			_s($h->startTag('ul')->startTag('li')->startTag('span', 'foo')));
		$this->assertEquals('aaa</span></li></ul>',
			_s($h->text('aaa')->endAllTags()));
		$h->popContext();
		$this->assertEquals('</dl>',
			_s($h->endAllTags()));
		$h->popContext();
		$this->assertEquals('<div><p></p></div>', _s($h->endAllTags()));

		$h->startTag('ol');
		$h->popContext(); //No context is on the stack.
		$this->assertEquals('<ol></ol>', _s($h->endTag()));
	}

	public function testPopContextReturnsString() {
		$h = $this->h;

		$h->startTag('div')->startTag('p');

		$h->pushNewContext();
		$this->assertEquals('', _s($h));
		$this->assertEquals('', _s($h->endTag()->endTag()));
		$this->assertEquals('<dl><dt></dt>',
			_s($h->startTag('dl')->startTag('dt')->endTag()));
		$h->pushNewContext();

		$this->assertEquals('', _s($h));
		$this->assertEquals('', _s($h->endTag()));

		$h->startTag('ul')->startTag('li')->startTag('span', 'foo')
			->text('aaa')->endAllTags();

		$this->assertEquals('<ul><li><span class="foo">aaa</span></li></ul>',
			$h->popContext());

		$this->assertEquals('</dl>',
			$h->endAllTags()->popContext());

		$this->assertEquals('<div><p></p></div>', _s($h->endAllTags()));

		$h->startTag('ol');
		$this->assertEquals('', $h->popContext()); //No context is on the stack.
		$this->assertEquals('<ol></ol>', _s($h->endTag()));
	}

	public function testPopContextReturnsStringHandlySyntax() {
		$this->h->div('outer');

		$this->assertEquals('<div class="inner"><p>foo</p></div>',
			$this->h->pushNewContext
			->div('inner')->p->text('foo')->endAllTags->popContext);

		$this->assertEquals('<div class="outer"></div>',
			_s($this->h->endAllTags));
	}

	public function testShortCutMethods1() {
		$h = $this->h;

		$this->assertEquals('<div class="foo"><p title="aaaaaa"><strong>bar</strong></p></div>',
			_s($h->div("foo")
			->p(array('title' => "aaaaaa"))
			->strong
			->text('bar')
			->end
			->end
			->endAllTags));

		$this->assertEquals('<table><tr><td>bar</td></tr></table>',
			_s($h->table
			->tr->td(null, 'bar')->end->end));
	}

	public function testShortCutMethods2() {
		$h = $this->h;

		$this->assertEquals('<div class="foo"><p title="aaaaaa"><strong>bar</strong></p></div>',
			_s($h->div("foo")
			->p(array('title' => "aaaaaa"))
			->strong
			->text('bar')
			->endstrong
			->endp
			->enddiv));

		$this->assertEquals("<table><tr><td>bar</td>\n</tr></table>",
			_s($h->table
			->tr->td(null, 'bar')->nl->endtable));
	}

	public function testShortCutMethodsFlipArgs() {
		$h = $this->h;

		$this->assertEquals('<div class="foo">&lt;aaa&gt;</div>', _s($h->div_("<aaa>", "foo")));
		$this->assertEquals('<div>&lt;aaa&gt;</div>', _s($h->div_("<aaa>")));
		$this->assertEquals('<div>&lt;aaa&gt;</div>', _s($h->div_("<aaa>", null)));
		$this->assertEquals('<div><aaa></div>', _s($h->div_("<aaa>", null, false)));

		$this->assertEquals('<div>', _s($h->div_()));
		$this->assertEquals('<div>', _s($h->div_(null)));
		$this->assertEquals('<div class="foo">', _s($h->div_(null, "foo")));
	}

	public function testAliasMethod() {
		$h = $this->h;

		$h->aliasMethod('newline2', 'newline');
		$h->aliasMethod('div2', 'div');

		$this->assertEquals("<div>\n</div>",
			_s($this->h->div2->newline2->end));
	}

}

