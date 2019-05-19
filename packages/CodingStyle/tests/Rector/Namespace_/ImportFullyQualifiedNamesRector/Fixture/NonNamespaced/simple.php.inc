<?php

function importFromThisFunction() {
    $baz = new \Foo\Bar;
}

?>
-----
<?php

use \Foo\Bar;

function importFromThisFunction() {
    $baz = Bar;
}

?>

