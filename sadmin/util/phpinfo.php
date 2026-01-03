<?php
/*include_once("serverstatus.php");
echo "<br><br>";*/
phpinfo();
echo "<pre>";
echo '변수';
print_r(get_defined_vars());
echo '<hr>클라스';
print_r(get_declared_classes());
echo '<hr>상수';
print_r(get_defined_constants());
echo '<hr>함수';
print_r(get_defined_functions());
echo "</pre>";
?>