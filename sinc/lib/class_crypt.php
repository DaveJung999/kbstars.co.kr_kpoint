<?php
/**
http://www.phpclasses.org/browse.html/package/851.html

Copyright (C) 2002 Jason Sheets <jsheets@shadonet.com>.
All rights reserved.

THIS SOFTWARE IS PROVIDED BY THE PROJECT AND CONTRIBUTORS ``AS IS'' AND
Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions
are met:

1. Redistributions of source code must retain the above copyright
	notice, this list of conditions and the following disclaimer.
	
2. Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in the
	documentation and/or other materials provided with the distribution.
	
3. Neither the name of the project nor the names of its contributors
	may be used to endorse or promote products derived from this software
	without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE PROJECT AND CONTRIBUTORS ``AS IS'' AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED.	IN NO EVENT SHALL THE PROJECT OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
SUCH DAMAGE.

**/

class CRYPT_CLASS {
	var $cipher; // cipher to encrypt with
	var $defaultmode = 'cfb'; // default encryption mode to use
	var $defaultcipher = 'twofish'; // default cipher to use
	var $key; // encryption/decription key
	var $mode; // encryption mode to use

	// constructor for CRYPT_CLASS
	function __construct(){
		if (!function_exists('openssl_encrypt')){
			?>
			<html><head><title>OpenSSL not available</title></head><body>
			<h3>OpenSSL not available</h3>
			<p>In order to use crypt class you must have OpenSSL enabled in PHP. Please contact your hosting provider or system admin.</p>
			</body></html>
			<?php
			exit;
		}
	}

	// creates an IV
	function create_iv(){
		if ((!isset($this->cipher)) || (!isset($this->mode))){
			trigger_error('create_iv: cipher and mode must be set before using create_iv', E_USER_ERROR);
			return 0;
		}
		$ivlen = openssl_cipher_iv_length($this->_build_method());
		$iv = openssl_random_pseudo_bytes($ivlen);
		if (!$iv){
			trigger_error('create_iv: unable to create iv', E_USER_ERROR);
		}
		return $iv;
	}

	function decrypt($encrypted){
		if ((!isset($this->cipher)) || (!isset($this->mode)) || (!isset($this->key))){
			trigger_error('decrypt: cipher, mode, and key must be set before using decrypt', E_USER_ERROR);
		}
		$data = base64_decode($encrypted);
		$ivlen = openssl_cipher_iv_length($this->_build_method());
		$iv = substr($data, 0, $ivlen);
		$data = substr($data, $ivlen);
		$decrypted = openssl_decrypt($data, $this->_build_method(), $this->key, OPENSSL_RAW_DATA, $iv);
		unset($data);
		return $decrypted;
	}

	function encrypt($data){
		if ((!isset($this->cipher)) || (!isset($this->mode)) || (!isset($this->key))){
			trigger_error('encrypt: cipher, mode, and key must be set before using encrypt', E_USER_ERROR);
		}
		$iv = $this->create_iv();
		$encrypted_data = openssl_encrypt($data, $this->_build_method(), $this->key, OPENSSL_RAW_DATA, $iv);
		unset($data);
		return base64_encode($iv . $encrypted_data);
	}

	function get_cipher(){
		return $this->cipher;
	}

	function get_key(){
		return $this->key;
	}

	function get_mode(){
		return $this->mode;
	}

	function set_cipher($ciphername){
		$available = openssl_get_cipher_methods();
		if (in_array(strtolower($ciphername . '-' . $this->defaultmode), array_map('strtolower', $available))){
			$this->cipher = $ciphername;
			return 1;
		} else {
			return 0;
		}
	}

	function set_key($encryptkey){
		if ((!isset($this->cipher)) || (!isset($this->mode))){
			trigger_error('set_key: cipher and mode must be set before using set_key', E_USER_ERROR);
		}
		if (!empty($encryptkey)){
			$keylen = strlen($encryptkey);
			$method = $this->_build_method();
			$expected_keylen = 32; // default for AES-256
			if ($keylen < $expected_keylen){
				$encryptkey = hash('sha256', $encryptkey, true);
			} elseif($keylen > $expected_keylen){
				$encryptkey = substr($encryptkey, 0, $expected_keylen);
			}
			$this->key = $encryptkey;
		} else {
			return 0;
		}
	}

	function set_mode($encryptmode){
		$available = openssl_get_cipher_methods();
		if (in_array(strtolower($this->defaultcipher . '-' . $encryptmode), array_map('strtolower', $available))){
			$this->mode = $encryptmode;
		} else {
			return 0;
		}
	}

	function _build_method(){
		return strtolower($this->cipher . '-' . $this->mode);
	}
}
?>
