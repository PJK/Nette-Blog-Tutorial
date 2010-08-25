<h1>dibi prefix & substitute example</h1>
<?php

require_once 'Nette/Debug.php';
require_once '../dibi/dibi.php';


dibi::connect(array(
	'driver'   => 'sqlite',
	'database' => 'sample.sdb',
));



// create new substitution :blog:  ==>  wp_
dibi::addSubst('blog', 'wp_');

dibi::test("UPDATE :blog:items SET [text]='Hello World'");
// -> UPDATE wp_items SET [text]='Hello World'



// create new substitution :: (empty)  ==>  my_
dibi::addSubst('', 'my_');

dibi::test("UPDATE [database.::table] SET [text]='Hello World'");
// -> UPDATE [database].[my_table] SET [text]='Hello World'



// create substitution fallback
function substFallBack($expr)
{
	if (defined($expr)) {
		return constant($expr);
	} else {
		return 'the_' . $expr;
	}
}

dibi::setSubstFallBack('substFallBack');

dibi::test("UPDATE [:account:user] SET [name]='John Doe', [active]=:true:");
// -> UPDATE [the_accountuser] SET [name]='John Doe', [active]=1
