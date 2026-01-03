<?php 

//보고자하는 Class파일을 include 시킨다. 
include "./class_phpBarGraph2.php"; 
function print_vars( $obj ) { 
$arr = get_object_vars ( $obj ); 
while ( list($prop, $val) = each($arr) ) 
echo "\t{$prop} = {$var}\n"; 
} 
function print_methods( $obj ) { 
$arr = get_class_methods( get_class( $obj ) ); 
foreach ( $arr as $method ) 
echo "\tfunction {$method}()\n"; 
} 
function class_parentage( $obj, $class ) { 
global $$obj; 
if( is_subclass_of( $$obj, $class ) ) { 
echo "Object {$obj} belongs to class ". get_class($$obj); 
echo " a subclass of {$class}\n "; 
}else{ 
echo " Object {$obj} does not belong to a subclass of $class \n"; 
} 
} 
//클래스 이름을 메모리에 할당.. 
$mailer = new PhpBarGraph; 
echo "mailer: Methods \n"; 
print_methods( $mailer ); 
echo"\n\n"; 
?> 