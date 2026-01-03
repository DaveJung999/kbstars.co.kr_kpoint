<?php
/* TripleDES under 2.2.x
$key = "this is a very secret key";
$input = "Let us meet at 9 o'clock at the secret place.";

echo $encrypted_data = mcrypt_ecb (MCRYPT_3DES, $key, $input, MCRYPT_ENCRYPT);
*/

/*
	$key = "this is a very secret key";
	$input = "Let us meet at 9 o'clock at the secret place.";

	$td = mcrypt_module_open ('tripledes', '', 'ecb', '');
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);
	mcrypt_generic_init ($td, $key, $iv);
	echo $encrypted_data = mcrypt_generic ($td, $input);
	mcrypt_generic_end ($td);
*/



echo "cipher(암호문) -> mode(암호방법)<br>";
$algorithms = mcrypt_list_algorithms ("/usr/local/lib/libmcrypt");
foreach ($algorithms as $cipher) {
	$modes = mcrypt_list_modes ();
	foreach ($modes as $mode) {
		echo "&nbsp;&nbsp; {$cipher} -> {$mode} : blocksize" . @mcrypt_get_block_size ($cipher, $mode). ",  iv_size :" . @mcrypt_get_iv_size ($cipher, $mode) . "<br />\n";
	}
}



// returns encrypted text
// incoming: should be the $key that was encrypt
// with and the $plain_text that wants to be encrypted
function encrypt($key, $plain_text) {
	$plain_text = trim($plain_text);
	$iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
	$c_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $plain_text, MCRYPT_ENCRYPT, $iv);
	return trim(chop(base64_encode($c_t)));
}
// incoming: should be the $key that you encrypted
// with and the $c_t (encrypted text)
// returns plain text
function decrypt($key, $c_t) {
	$c_t =  trim(chop(base64_decode($c_t)));
	$iv = substr(md5($key), 0,mcrypt_get_iv_size (MCRYPT_CAST_256,MCRYPT_MODE_CFB));
	$p_t = mcrypt_cfb (MCRYPT_CAST_256, $key, $c_t, MCRYPT_DECRYPT, $iv);
	return trim(chop($p_t));
}



	/* Open the cipher */
	$td = mcrypt_module_open ('tripledes', '', 'cfb', '');


	/* Create the IV and determine the keysize length */
	$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_DEV_RANDOM);
	echo $ks = mcrypt_enc_get_key_size ($td);
	/* Create key */
	echo $key = substr (md5 ('very secret key'), 0, $ks);
	/* Intialize encryption */
	mcrypt_generic_init ($td, $key, $iv);
	/* Encrypt data */
	echo $encrypted = mcrypt_generic ($td, 'This is very important data');
	/* Terminate encryption handler */
	mcrypt_generic_deinit ($td);


	/* Initialize encryption module for decryption */
	mcrypt_generic_init ($td, $key, $iv);
	/* Decrypt encrypted string */
	$decrypted = mdecrypt_generic ($td, $encrypted);
	/* Terminate decryption handle and close module */
	mcrypt_generic_deinit ($td);
	mcrypt_module_close ($td);

	/* Show string */
	echo trim ($decrypted)."\n";
?>