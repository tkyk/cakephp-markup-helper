# CakePHP Markup Helper

MarkupHelper allows you to build complex (X)HTML by fluent interface.

    echo $this->Markup->div('class1')
		->p->text('Hello world')->end
		->end;

## Requirements

-  PHP 5.2.8 or later
-  CakePHP 2.0

## Installation

    cd app/Plugin/
    git clone git://github.com/tkyk/cakephp-markup-helper Markup

I recommend you to checkout a versioning tag rather than a development branch.

	cd app/Plugin/Markup
	git checkout x.y.z.w

## Usage

Load `Markup.Markup` in your controllers.

    class AppController extends Controller {
		public $helpers = array('Markup.Markup');
    }

And then, you can write (X)HTML with method chaining.

    echo $this->Markup->div('class1')
		->p->text('Hello world')->end
		->end;

Other helpers methods are also available in method chains.

	//The prefix f_ is FormHelper, h_ is HtmlHelper,
	//and <HelperName>_ is <HelperName>Helper.
    echo $this->Markup
		->f_create('Post')
		->f_input('username')
		->f_input('password')
		->h_link('Forget password?', array('action' => 'forget'))
		->f_end('Login')
		->div('news')
		->Paginator_numbers()
		->enddiv;

Check [Syntax Guide](http://wiki.github.com/tkyk/cakephp-markup-helper/syntax-guide) for more details about the plugin syntax.

