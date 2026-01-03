<?php
//03/09/24 http://www.kfu.com/~nsayer/encryption/
  define("KEY","its_a_secret");
  define("HASH",MHASH_SHA1);
  define("CIPHER",MCRYPT_TRIPLEDES);

  require("webcrypt.phpi");

  $blob = $_POST['blob'];

  if ($blob == "") {
	print("Don't run me directly. Run \"client.php3\" to see me work.");
	exit;
  }

  if (($pt=WEB_decrypt($blob))==FALSE)
	print("bad!");
  else
	print("Decode:<pre>".$pt."</pre>");
?>