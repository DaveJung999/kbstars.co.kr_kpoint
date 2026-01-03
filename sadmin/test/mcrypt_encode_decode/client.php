<?php
//03/09/24 http://www.kfu.com/~nsayer/encryption/

  define("KEY","its_a_secret");
  define("HASH",MHASH_SHA1);
  define("CIPHER",MCRYPT_TRIPLEDES);

  require("webcrypt.phpi");

  $pt = $_POST['pt'];

  if ($pt=="")
	$pt="This is a test message.";

  $blob=WEB_encrypt($pt);

  print("Encrypted blob:<pre>".chunk_split($blob)
	."\r\n</pre><P><HR><P>");

  $sock=fsockopen("www",80);
  fputs($sock,""
	."POST /~nsayer/encryption/software/server.php3 HTTP/1.0\r\n"
	."User-Agent: Nick's webcrypt PHP3 client\r\n"
	."Content-Length: ".(strlen(rawurlencode($blob))+5)."\r\n"
	."Content-Type: application/x-www-form-urlencoded\r\n"
	."\r\nblob=".rawurlencode($blob));
  while(fgets($sock,4096)!="\r\n") ; /* trash result header */
  fpassthru($sock);
?>
