<?php

namespace Rector\Tests\TypeDeclaration\Rector\YodaConditionsRector\Fixture;
const A_CONST = 'x';
$a = 'x';
if ($a === 'x') {
    $a = 'xx';
}
if ($a === null) {
	$a = 'xx';
}
if ($a == false) {
	$a = 'xx';
}
if ($a === A_CONST) {
	$a = 'xx';
}
if (strlen($a) === 1) {
    $a = 'xx';
}
if ($a === 0 || $a === rand()) {
    return $a;
}
?>
-----
<?php

namespace Rector\Tests\TypeDeclaration\Rector\YodaConditionsRector\Fixture;
const A_CONST = 'x';
$a = 'x';
if ('x' === $a) {
    $a = 'xx';
}
if (null === $a) {
	$a = 'xx';
}
if (false == $a) {
	$a = 'xx';
}
if (A_CONST === $a) {
	$a = 'xx';
}
if (strlen($a) === 1) {
    $a = 'xx';
}
if (0 === $a || rand() === $a) {
    return $a;
}
?>
