<?php
$a='a:1:{s:6:"upfile";a:1:{s:4:"name";s:8:"park.asp";}}';
$upfiles=unserialize($a);
if(!is_array($upfiles))

	$upfiles['upfile'][name]="sun.asp";
print_r($upfiles);
echo "<br>";
echo serialize($upfiles);
?>