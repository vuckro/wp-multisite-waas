<?php

namespace Rector\Tests\TypeDeclaration\Rector\YodaConditionsRector\Fixture;
const A_CONST = 'x';
$arr = array('x'=>1);
$obj = (object) $arr;
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
if ($obj->x === 1) {
    echo $obj->x;
}
if ($arr['x'] === 1) {
    echo $arr['x'];
}
?>
-----
<?php

namespace Rector\Tests\TypeDeclaration\Rector\YodaConditionsRector\Fixture;
const A_CONST = 'x';
$arr = array('x'=>1);
$obj = (object) $arr;
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
if (1 === $obj->x) {
    echo $obj->x;
}
if (1 === $arr['x']) {
    echo $arr['x'];
}
?>
