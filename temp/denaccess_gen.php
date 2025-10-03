#!/usr/local/bin/php-7.2
<?php
# denaccess_gen.php	This file generates a modern encryption key for the project
# created		2020/09/16 by Dave Henderson (dhenderson@cliquesoft.org)
# updated		2020/09/24 by Dave Henderson (dhenderson@cliquesoft.org)


class Cipher {
	# @return type
	static public function create_encryption_key()
		{ return base64_encode(sodium_crypto_secretbox_keygen()); }

	# Encrypt a value and return it!
	# $val	value to encrypt
	# $key	encryption key (via create_encryption_key())
	static function encrypt($val, $key) {
		$key_decoded = base64_decode($key);
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);

		$cipher = base64_encode($nonce . sodium_crypto_secretbox($val, $nonce, $key_decoded));
		sodium_memzero($val);
		sodium_memzero($key_decoded);
		return $cipher;
	}

	# Decrypt a value and return it!
	# $val - value to decrypt
	# $key - encryption key
	static function decrypt($val, $key) {
		$decoded = base64_decode($val);
		$key_decoded = base64_decode($key);

		if ($decoded === false) { throw new Exception('Error: The encoding failed.'); }
		if (mb_strlen($decoded, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES))
			{ throw new Exception('Error: The message was truncated.'); }

		$nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
		$ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

		$plain = sodium_crypto_secretbox_open($ciphertext, $nonce, $key_decoded);
		if ($plain === false)
			{ throw new Exception('Error: The message was tampered with in transit.'); }
		sodium_memzero($ciphertext);
		sodium_memzero($key_decoded);
		return $plain;
	}
}


# create the unique encryption key for this project
$salt = Cipher::create_encryption_key();

# define the default file value (in current directory)
$file = './denaccess';

# if the user passed an alternative file, store it now
if (count($argv) == 2) { $file = $argv[1]; }

# write the information to file
file_put_contents($file, $salt."\n", FILE_APPEND);

exit();


?>

