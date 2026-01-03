<html>
 <head>
  <style type="text/css">
	body {
	margin-top: 5px;
	padding-top: 5px;
	margin-left: 5px;
	padding-left: 5px;
	}
	.menu {
	border: 1px, solid, #00004F;
	}
	.choice {	
	
	}
	.sub {
	border: 1px solid #000000;
	}
  </style>
 </head>
 <body>
<?php
	// Testing menu class
	include('cascading_verticle_menu.php');

	// Choices... choice(up image, down image, URL, image alt, css_class)
	$c1 = new choice('test_up.gif','test_down.gif','test.php', 'TEST', 'choice');
	$c2 = new choice('test_up.gif','test_down.gif','index.html', 'INDEX', 'choice');
	$c3 = new choice('test_up.gif','test_down.gif','menu_test.php', 'THIS', 'choice');
	$sc1 = new choice('test_up.gif','test_down.gif','index.html', 'SUB1', 'sub');
	$sc2 = new choice('test_up.gif','test_down.gif','index.html', 'SUB2', 'sub');
	$sc3 = new choice('test_up.gif','test_down.gif','index.html', 'SUB3', 'sub');
	$sc4 = new choice('test_up.gif','test_down.gif','index.html', 'SUB-SUB1', 'sub-sub');
	$sc5 = new choice('test_up.gif','test_down.gif','index.html', 'SUB-SUB1', 'sub-sub');
	$sc6 = new choice('test_up.gif','test_down.gif','index.html', 'SUB-SUB1', 'sub-sub');
	$sc7 = new choice('test_up.gif','test_down.gif','index.html', 'SUB-SUB1', 'sub-sub');

	// Vert menu... cascading_verticle_menu(top, left, height, width, css_class)
	$v = new cascading_verticle_menu(10, 10, 20, 100, 'menu');

	// Elements must be added in a top down manner
	// Add some elements
	$v->add($c1);
	$v->add($c2);
	$v->add($c3);

	// Second level
	$c1->add($sc1);
	$c1->add($sc2);
	$c3->add($sc6);
	$c3->add($sc7);

	// Third level
	$sc2->add($sc4);
	$sc2->add($sc5);

	// Fourth level
	$sc4->add($sc3);

	// Output vertical menu
	echo $v->write();
?>
</body>
</html>