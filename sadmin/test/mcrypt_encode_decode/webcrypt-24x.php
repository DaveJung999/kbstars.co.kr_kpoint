<?php
//03/09/24 http://www.kfu.com/~nsayer/encryption/

/*
 * Webcrypt.phpi -- Copyright 1999 Nick Sayer
 * All rights reserved.
 *
 *
 * Builds a secure message system with PHP, mhash and mcrypt.
 *
 * Before use, the following must be defined:
 *
 *	HASH - the MHASH_ algorithm selector for the hash.
 *	CIPHER - the MCRYPT_ algorithm selector for the cipher.
 *	KEY - the secret key to encrypt and decrypt the messages.
 *
 * To encrypt, a plaintext message can consist of any string.
 * It is first run through HASH to get a checksum. The checksum
 * is put at the front of the message because A> It perterbs
 * the CIPHER due to its pseudo-random nature and B> because
 * unlike the plaintext, its length is known.
 *
 * The key is also run through the HASH, since keys chosen by
 * humans typically are not very good, usually consisting
 * only of about 96 out of 256 values and generally being
 * too short.
 *
 * A random initial vector is chosen to be later prepended to
 * the ciphertext.
 *
 * The hash and plaintext are then run through the selected
 * CIPHER with the HASHed key and iv. mcrypt routines sometimes append
 * garbage to the ciphertext, which would not have been run through
 * the hash. So both the encoder carefully trims the ciphertext
 * to be the same length as the plaintext (the decoder must also do
 * the opposite).
 *
 * Finally, the ciphertext and the initial vector are run through
 * base64_encode() in order to make the mess printable. Note
 * that in order to be mailable or printable, the encoded output
 * should be run through chunk_split(), and to be passed to a
 * HTTP server it should be run through rawurlencode().
 *
 */
/*
 * Take in arbitrary plaintext and make an encrypted message.
 * The output is base64 encoded.
 */

  function WEB_encrypt($pt) {

	$realkey = mhash(HASH,KEY);
	$td = mcrypt_module_open(CIPHER, "", MCRYPT_MODE_OFB, "");
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_DEV_URANDOM);

	mcrypt_generic_init($td, $realkey, $iv);
	$blob = mcrypt_generic($td, mhash(HASH,$pt). $pt);
	mcrypt_generic_end($td);

	return base64_encode($iv. $blob);
  }

/*
 * Decrypt previously encrypted plaintext message. Input is
 * expected to be base64 encoded, output is arbitrary string
 * or FALSE if decode failed.
 */
  function WEB_decrypt($blob) {

	$realkey=mhash(HASH,KEY);
	$rawblob=base64_decode($blob); /* binary blob */
	$td = mcrypt_module_open(CIPHER, "", MCRYPT_MODE_OFB, "");
	$iv=substr($rawblob,0,mcrypt_enc_get_iv_size($td)); /* IV */
	if (strlen($iv)<mcrypt_enc_get_iv_size($td))
	  return FALSE;
	$ct=substr($rawblob,mcrypt_enc_get_iv_size($td)); /* CipherText */
	mcrypt_generic_init($td, $realkey, $iv);
	$unblob=mdecrypt_generic($td,$ct);
	mcrypt_generic_end($td);

	$pt=substr($unblob,mhash_get_block_size(HASH));
	$check=substr($unblob,0,mhash_get_block_size(HASH));

	if ($check != mhash(HASH,$pt))
	  return FALSE;
	else
	  return $pt;
  }
?>
